<?php

namespace Kodzitsu\Queue;

readonly class ProcessingResult
{
    public function __construct(private string $response)
    {
    }

    public function getResponse(): string
    {
        return $this->response;
    }
}
