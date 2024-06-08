<?php

namespace Kodzitsu\Queue\Storage;

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\QueueException;

class File implements StorageInterface
{
    private const FORMAT = '[STATE]_[SORT_ID]_[MSG_ID].msg';

    private int $sort_pointer = 0;

    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            throw new QueueException(sprintf('Directory "%s" does not exist or is not a directory', $this->directory));
        }
        if (!is_writable($this->directory)) {
            throw new QueueException(sprintf('Directory "%s" is not writable', $this->directory));
        }
        $this->directory = rtrim($this->directory, '/') . '/';
    }

    public function enqueue(Message ...$messages): void
    {
        foreach ($messages as $message) {
            $message->setState(Message\State::ENQUEUED);
            file_put_contents($this->directory . $this->newFilename($message), (string)$message->getPayload());
        }
    }

    public function next(): ?Message
    {
        $enqueued_files = glob(sprintf('%s%s_*.msg', $this->directory, Message\State::ENQUEUED->name));

        // @codeCoverageIgnoreStart
        if ($enqueued_files === false) {
            throw new QueueException('Error while trying to access storage');
        }
        // @codeCoverageIgnoreEnd

        if (empty($enqueued_files)) {
            return null;
        }

        do {
            $current = current($enqueued_files);
            if ($current === false) {
                return null;
            }
            next($enqueued_files);
            [$state, $sort_id, $id] = explode('_', pathinfo($current, PATHINFO_FILENAME));
            $sort_id = (int)$sort_id;
            $payload = file_get_contents($current);
            if ($this->sort_pointer === 0) {
                // First run doesn't need this loop
                break;
            }
        } while ($sort_id <= $this->sort_pointer);

        $this->sort_pointer = $sort_id;

        return Message::load($id, $state, $payload);
    }

    public function dequeue(Message $message): bool
    {
        $message_file = glob(sprintf(
            '%s%s_*_%s.msg',
            $this->directory,
            Message\State::ENQUEUED->name,
            $message->getId()
        ));

        // @codeCoverageIgnoreStart
        if ($message_file === false) {
            throw new QueueException('Error while trying to access storage');
        }
        // @codeCoverageIgnoreEnd

        if (!$message_file) {
            return false;
        }

        foreach ($message_file as $file) {
            unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        $pattern = sprintf('%s%s_*.msg', $this->directory, Message\State::ENQUEUED->name);
        foreach (glob($pattern) as $filename) {
            unlink($filename);
        }
        $this->sort_pointer = 0;

        return (bool)count(glob($pattern));
    }

    public function isBusy(): bool
    {
        return (bool)count(glob(sprintf('%s%s_*.msg', $this->directory, Message\State::RUNNING->name)));
    }

    public function countEnqueued(): int
    {
        return count(glob(sprintf('%s/%s_*.msg', $this->directory, Message\State::ENQUEUED->name)));
    }

    public function countDone(): int
    {
        return count(glob(sprintf('%s/%s_*.msg', $this->directory, Message\State::DONE->name)));
    }

    public function markRunning(Message $message): void
    {
        $message_file = glob(sprintf(
            '%s%s_*_%s.msg',
            $this->directory,
            Message\State::ENQUEUED->name,
            $message->getId()
        ));

        // @codeCoverageIgnoreStart
        if ($message_file === false) {
            throw new QueueException('Error while trying to access storage');
        }
        // @codeCoverageIgnoreEnd

        if (!$message_file) {
            throw new QueueException('Unable to mark running a non-existent message');
        }

        // @codeCoverageIgnoreStart
        if (count($message_file) > 1) {
            throw new QueueException(sprintf(
                'This should not happen, but it seems that two messages have same ID (%s)',
                $message->getId()
            ));
        }
        // @codeCoverageIgnoreEnd

        if ($this->isBusy()) {
            throw new QueueException('Only single message can be run at the time');
        }

        $message->setState(Message\State::RUNNING);
        $this->changeState(current($message_file), Message\State::ENQUEUED, Message\State::RUNNING);
    }

    public function markDone(Message $message): void
    {
        if (!$this->isBusy()) {
            throw new QueueException('Some message must be first marked RUNNING to be marked DONE afterwards');
        }

        $message_file = glob(sprintf(
            '%s%s_*_%s.msg',
            $this->directory,
            Message\State::RUNNING->name,
            $message->getId()
        ));

        // @codeCoverageIgnoreStart
        if ($message_file === false) {
            throw new QueueException('Error while trying to access storage');
        }
        // @codeCoverageIgnoreEnd

        if (!$message_file) {
            throw new QueueException('Could not find given message in RUNNING state');
        }

        // @codeCoverageIgnoreStart
        if (count($message_file) > 1) {
            throw new QueueException(sprintf(
                'It seems that two or more messages have been marked RUNNING already (%s). Fix it first.',
                implode(', ', $message_file)
            ));
        }
        // @codeCoverageIgnoreEnd

        $message->setState(Message\State::DONE);
        $this->changeState(current($message_file), Message\State::RUNNING, Message\State::DONE);
    }

    private function newFilename(Message $message): string
    {
        $enqueued_files = glob(sprintf('%s%s_*.msg', $this->directory, Message\State::ENQUEUED->name));

        $last = array_pop($enqueued_files);
        if ($last === null) {
            $sort_id = 0;
        } else {
            [$state, $sort_id, $id] = explode('_', pathinfo($last, PATHINFO_FILENAME));
            $sort_id = (int)$sort_id;
        }
        $sort_id++;

        $sort_id = str_pad((string)$sort_id, 6, '0', STR_PAD_LEFT);

        return strtr(
            self::FORMAT,
            [
                '[STATE]' => $message->getState()->name,
                '[SORT_ID]' => $sort_id,
                '[MSG_ID]' => $message->getId(),
            ]
        );
    }

    private function changeState(string $filename, Message\State $from, Message\State $to): void
    {
        [$state, $sort_id, $id] = explode('_', pathinfo($filename, PATHINFO_FILENAME));

        rename(
            $this->directory . strtr(
                self::FORMAT,
                [
                    '[STATE]' => $from->name,
                    '[SORT_ID]' => $sort_id,
                    '[MSG_ID]' => $id,
                ]
            ),
            $this->directory . strtr(
                self::FORMAT,
                [
                    '[STATE]' => $to->name,
                    '[SORT_ID]' => $sort_id,
                    '[MSG_ID]' => $id,
                ]
            ),
        );
    }
}
