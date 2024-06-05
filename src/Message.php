<?php

namespace Kodzitsu\Queue;

class Message
{
    public const STATUS_ENQUEUED = 'ENQUEUED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';

    private string $id;
    private string $status;

    public function __construct(private ?string $payload = null)
    {
        $this->id = uniqid();
        $this->status = self::STATUS_ENQUEUED;
    }

    /**
     * Use this method to load message from storage (so keep its id)
     */
    public static function load(string $id, string $status, ?string $payload): self
    {
        self::checkStatus($status);
        $message = new Message($payload);
        $message->id = $id;
        $message->status = $status;

        return $message;
    }

    public function asEnqueued(): self
    {
        $clone = clone $this;
        $clone->status = self::STATUS_ENQUEUED;

        return $clone;
    }

    public function asRunning(): self
    {
        $clone = clone $this;
        $clone->status = self::STATUS_RUNNING;

        return $clone;
    }

    public function asDone(): self
    {
        $clone = clone $this;
        $clone->status = self::STATUS_DONE;

        return $clone;
    }

    public function withPayload(string $payload): self
    {
        $clone = clone $this;
        $clone->payload = $payload;

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

    public static function checkStatus(string $status): void
    {
        $valid_statuses = [
            self::STATUS_ENQUEUED,
            self::STATUS_RUNNING,
            self::STATUS_DONE,
        ];

        if (
            !in_array(
                $status,
                $valid_statuses
            )
        ) {
            throw new QueueException(sprintf(
                'Invalid message status: "%s", valid are: %s',
                $status,
                join(', ', $valid_statuses)
            ));
        };
    }
}
