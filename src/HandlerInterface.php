<?php

namespace Kodzitsu\Queue;

interface HandlerInterface
{
    public function process(Message $message): ProcessingResult;
}
