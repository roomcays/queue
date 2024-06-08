<?php

namespace Kodzitsu\Queue;

class Message
{
    private string $id;
    private Message\State $state;

    public function __construct(private ?string $payload = null)
    {
        $this->id = uniqid();
        $this->state = Message\State::ENQUEUED;
    }

    /**
     * Use this method to load message from storage (so keep its id)
     */
    public static function load(string $id, string $state, ?string $payload): self
    {
        $message = new Message($payload);
        $message->id = $id;
        try {
            $message->state = Message\State::fromString($state);
        } catch (\UnhandledMatchError $exception) {
            throw new QueueException(
                sprintf(
                    'Invalid message state: "%s", valid are: %s',
                    $state,
                    implode(', ', array_map(fn(Message\State $state) => $state->name, Message\State::cases()))
                ),
                0,
                $exception
            );
        }

        return $message;
    }

    public function setState(Message\State $state): self
    {
        $this->state = $state;

        return $this;
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

    public function getState(): Message\State
    {
        return $this->state;
    }
}
