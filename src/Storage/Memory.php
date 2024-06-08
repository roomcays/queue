<?php

namespace Kodzitsu\Queue\Storage;

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\QueueException;

/**
 * Volatile storage for testing/debug purposes
 */
class Memory implements StorageInterface
{
    private array $queue = [];
    private array $finished = [];
    private ?Message $running = null;

    public function enqueue(Message ...$messages): void
    {
        foreach ($messages as $message) {
            // Force message's state to ENQUEUED regardless of what it holds originally
            $message->setState(Message\State::ENQUEUED);
            $this->queue[$message->getId()] = $message;
        }
    }

    public function next(): ?Message
    {
        $message = current($this->queue);
        if ($message === false) {
            return null;
        }
        next($this->queue);

        return $message;
    }

    public function dequeue(Message $message): bool
    {
        if (!isset($this->queue[$message->getId()])) {
            return false;
        }

        unset($this->queue[$message->getId()]);

        return true;
    }

    public function clear(): bool
    {
        $this->queue = [];

        return true;
    }

    public function isBusy(): bool
    {
        return $this->running instanceof Message;
    }

    public function markRunning(Message $message): void
    {
        if (!isset($this->queue[$message->getId()])) {
            throw new QueueException('Unable to mark running a non-existent message');
        }
        if ($this->isBusy()) {
            throw new QueueException('Only single message can be run at the time');
        }
        $message->setState(Message\State::RUNNING);
        $this->running = $message;
        unset($this->queue[$message->getId()]);
    }

    public function markDone(Message $message): void
    {
        if ($this->running === null) {
            throw new QueueException('Unable to mark done a message when it is not running');
        }
        if ($this->running->getId() !== $message->getId()) {
            throw new QueueException('Actually running message is not the one given');
        }
        $message->setState(Message\State::DONE);
        $this->finished[$message->getId()] = $message;
        $this->running = null;
    }

    public function countEnqueued(): int
    {
        if ($this->running instanceof Message) {
            return count(array_filter($this->queue, function (Message $message) {
                return $message->getId() !== $this->running->getId();
            }));
        }

        return count($this->queue);
    }

    public function countDone(): int
    {
        return count($this->finished);
    }
}
