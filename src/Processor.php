<?php

namespace Kodzitsu\Queue;

readonly class Processor
{
    public function __construct(private StorageInterface $storage, private HandlerInterface $handler)
    {
    }

    public function check(): false|ProcessingResult
    {
        if ($message = $this->storage->next()) {
            return $this->process($message);
        }

        return false;
    }

    private function process(Message $message): ProcessingResult
    {
        $this->storage->markRunning($message);
        $result = $this->handler->process($message);
        $this->storage->markDone($message);

        return $result;
    }

    public function enqueue(Message $message): void
    {
        $this->storage->enqueue($message->asEnqueued());
    }

    public function dequeue(Message $message): bool
    {
        return $this->storage->dequeue($message);
    }
}
