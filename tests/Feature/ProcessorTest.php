<?php

use Kodzitsu\Queue\Handler\DummyHandler;
use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\Processor;
use Kodzitsu\Queue\Storage\Memory as MemoryStorage;

it('can process one message', function () {
    $storage = new MemoryStorage();
    $message = (new Message())->withPayload('foobar');
    $storage->enqueue($message);
    $processor = new Processor($storage, new DummyHandler());
    $result = $processor->check();
    expect($result)->not->toBeNull()->and($result->getResponse())->toBe('foobar');
    $result = $processor->check();
    expect($result)->toBeFalse();
});

it('can process two messages in correct order (FIFO)', function () {
    $storage = new MemoryStorage();
    $storage->enqueue(new Message('foobar'));
    $storage->enqueue(new Message('baz'));
    $processor = new Processor($storage, new DummyHandler());
    $result = $processor->check();
    expect($result)->not->toBeNull()->and($result->getResponse())->toBe('foobar');
    $result = $processor->check();
    expect($result)->not->toBeNull()->and($result->getResponse())->toBe('baz');
    $result = $processor->check();
    expect($result)->toBeFalse();
});
