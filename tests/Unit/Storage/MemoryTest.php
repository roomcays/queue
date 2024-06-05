<?php

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\QueueException;
use Kodzitsu\Queue\Storage\Memory as MemoryStorage;

it('it can enqueue message', function () {
    $memory_storage = new MemoryStorage();
    $memory_storage->enqueue((new Message('foobar')));
    $message = $memory_storage->next();
    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->getPayload())->toBe('foobar')
        ->and($message->getStatus())->toBe('ENQUEUED');
});

it('it can dequeue a message', function () {
    $memory_storage = new MemoryStorage();
    $message = new Message();
    $memory_storage->enqueue($message);
    $result = $memory_storage->dequeue($message);
    $stored_message = $memory_storage->next();
    expect($stored_message)->toBeNull()->and($result)->toBeTrue();
    $result = $memory_storage->dequeue($message);
    expect($result)->toBeFalse();
});

it('can empty the queue', function () {
    $memory_storage = new MemoryStorage();
    $memory_storage->enqueue(new Message());
    expect($memory_storage->countEnqueued())->toBe(1);
    $memory_storage->clear();
    expect($memory_storage->countEnqueued())->toBe(0);
});

it('can check if some message is running', function () {
    $memory_storage = new MemoryStorage();
    expect($memory_storage->isBusy())->toBeFalse();
});

it('can throw an exception when trying to mark nonexistent message as done', function() {
    $memory_storage = new MemoryStorage();
    $memory_storage->markDone(new Message());
})->throws(QueueException::class, 'Unable to mark done a message when it is not running');

it('can count enqueued messages when one of them is running', function() {
    $memory_storage = new MemoryStorage();
    $message1 = new Message('to be marked as running');
    $message2 = new Message('other message');
    $memory_storage->enqueue($message1, $message2);
    expect($memory_storage->countEnqueued())->toBe(2);
    $memory_storage->markRunning($message1);
    expect($memory_storage->countEnqueued())->toBe(1);
});

it('can count finished messages', function() {
    $memory_storage = new MemoryStorage();
    $message1 = new Message('to be marked as running');
    $message2 = new Message('other message');
    $memory_storage->enqueue($message1, $message2);
    expect($memory_storage->countEnqueued())->toBe(2)->and($memory_storage->countDone())->toBe(0);
    $memory_storage->markRunning($message1);
    $memory_storage->markDone($message1);
    expect($memory_storage->countEnqueued())->toBe(1)->and($memory_storage->countDone())->toBe(1);
});

it('will not allow to run more than one message', function () {
    $memory_storage = new MemoryStorage();
    $message1 = new Message('to be marked as running');
    $message2 = new Message('also to be marked as running');
    $memory_storage->enqueue($message1, $message2);
    $memory_storage->markRunning($message1);
    $memory_storage->markRunning($message2);
})->throws(QueueException::class, 'Only single message can be run at the time');

it('will not let mark not already enqueued message to be marked as running', function() {
    $memory_storage = new MemoryStorage();
    $memory_storage->markRunning(new Message());
})->throws(QueueException::class, 'Unable to mark running a non-existent message');

it('will not let mark done message other than actually running', function() {
    $memory_storage = new MemoryStorage();
    $message1 = new Message('to be marked as running');
    $message2 = new Message('other message');
    $memory_storage->enqueue($message1, $message2);
    $memory_storage->markRunning($message1);
    $memory_storage->markDone($message2);
})->throws(QueueException::class, 'Actually running message is not the one given');
