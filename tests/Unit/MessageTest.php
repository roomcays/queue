<?php

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\QueueException;

it('has an ID', function () {
    $message = new Message();
    expect($message)
        ->toHaveProperty('id')->not->toBeEmpty()
        ->and($message->getId())->not->toBeEmpty();
});

it('has enqueued status', function () {
    $message = new Message();
    expect($message->getState())->toBe(Message\State::ENQUEUED)
        ->and($message->setState(Message\State::ENQUEUED)->getState())->toBe(Message\State::ENQUEUED);
});

it('has no payload', function () {
    $message = new Message();
    expect($message->getPayload())->toBeNull();
});

it('has some payload', function () {
    $message = new Message();
    expect($message->withPayload('foobar')->getPayload())->toBe('foobar');
});

it('loads', function() {
    $message = Message::load('123', 'RUNNING', 'foobar');
    expect($message->getPayload())->toBe('foobar')
        ->and($message->getId())->toBe('123')
        ->and($message->getState())->toBe(Message\State::RUNNING);
});

it('has invalid status upon loading', function() {
    Message::load('123', 'INVALID', 'foobar');
})->throws(
    QueueException::class,
    'Invalid message state: "INVALID", valid are: ENQUEUED, RUNNING, DONE'
);

it('can be marked as done', function () {
    expect((new Message())->setState(Message\State::DONE)->getState())->toBe(Message\State::DONE);
});
