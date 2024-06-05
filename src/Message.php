<?php

namespace Kodzitsu\Queue;

class Message
{
    public const STATUS_ENQUEUED = 'ENQUEUED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';

    private string $id;
    private ?string $payload = null;

    public function __construct(private string $status = self::STATUS_ENQUEUED)
    {
        $this->id = uniqid();
    }

    public function asEnqueued(): self
    {
        $clone = clone $this;
        $this->status = self::STATUS_ENQUEUED;

        return $clone;
    }

    public function withPayload(string $payload): self
    {
        $clone = clone $this;
        $this->payload = $payload;

        return $clone;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
