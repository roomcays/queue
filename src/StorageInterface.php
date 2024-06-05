<?php

namespace Kodzitsu\Queue;

interface StorageInterface
{
    public function enqueue(Message $message): void;

    public function next(): ?Message;

    public function dequeue(Message $message): bool;

    public function clear(): bool;

    public function isBusy(): bool;

    public function markRunning(Message $message): void;

    public function markDone(Message $message): void;
}
