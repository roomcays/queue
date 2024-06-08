<?php

namespace Kodzitsu\Queue\Handler;

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\ProcessingResult;

/**
 * Passes message's payload into result's response. Use for debug/testing purposes.
 */
class DummyHandler implements HandlerInterface
{
    public function process(Message $message): ProcessingResult
    {
        return new ProcessingResult((string)$message->getPayload());
    }
}
