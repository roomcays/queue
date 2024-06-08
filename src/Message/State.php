<?php

namespace Kodzitsu\Queue\Message;

enum State
{
    case ENQUEUED;
    case RUNNING;
    case DONE;

    public static function fromString(string $value): State
    {
        return match ($value) {
            'ENQUEUED' => State::ENQUEUED,
            'RUNNING' => State::RUNNING,
            'DONE' => State::DONE,
        };
    }
}
