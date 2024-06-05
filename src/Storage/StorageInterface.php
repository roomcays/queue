<?php

namespace Kodzitsu\Queue\Storage;

use Kodzitsu\Queue\Message;

interface StorageInterface
{
    public function enqueue(Message ...$messages): void;

    public function next(): ?Message;

    public function dequeue(Message $message): bool;

    public function clear(): bool;

    public function isBusy(): bool;

    public function countEnqueued(): int;

    public function countDone(): int;

    public function markRunning(Message $message): void;

    public function markDone(Message $message): void;
}
