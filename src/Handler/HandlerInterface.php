<?php

namespace Kodzitsu\Queue\Handler;

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\ProcessingResult;

interface HandlerInterface
{
    public function process(Message $message): ProcessingResult;
}
