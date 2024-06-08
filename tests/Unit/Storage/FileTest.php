<?php

use Kodzitsu\Queue\Message;
use Kodzitsu\Queue\QueueException;
use Kodzitsu\Queue\Storage\File as FileStorage;

function getQueuesTmpDir(): string {
    return getTmpDirectory() . '/queues';
}

function checkQueueDir(string $queues_tmp_dir): bool
{
    if (!is_dir(getTmpDirectory())) {
        return false;
    }

    if (!is_writable(getTmpDirectory())) {
        return false;
    }

    if (!is_dir($queues_tmp_dir)) {
        return false;
    }

    if (!is_writable($queues_tmp_dir)) {
        return false;
    }

    return true;
}

function rmDirRecursively(string $directory): bool
{
    $files = array_diff(scandir($directory), ['.', '..']);

    foreach ($files as $file) {
        is_dir("$directory/$file")
            ? rmDirRecursively("$directory/$file")
            : unlink("$directory/$file");
    }

    return rmdir($directory);
}

beforeAll(function () {
    if (file_exists(getQueuesTmpDir())) {
        rmDirRecursively(getQueuesTmpDir());
    }
    @mkdir(getQueuesTmpDir());
});

afterAll(function () {
    rmDirRecursively(getQueuesTmpDir());
});

afterEach(function () {
    array_map('unlink', glob(getQueuesTmpDir() . '/*.msg'));
});

it('constructs using some directory for queue storage', function () {
    new FileStorage(getQueuesTmpDir());
})->skip(checkQueueDir(getQueuesTmpDir()) !== false)->throwsNoExceptions();

it('recognizes when storage location is unavailable', function () {
    new FileStorage('/foobar/bar/baz');
})->throws(QueueException::class, 'Directory "/foobar/bar/baz" does not exist or is not a directory');

it('recognizes when storage location is not writable', function () {
    new FileStorage('/');
})->throws(QueueException::class, 'Directory "/" is not writable');

it('can store and load single message', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message = new Message('foobar');
    $queue->enqueue($message);
    expect($queue->countEnqueued())->toBe(1);
    $retrieved = $queue->next();
    expect($retrieved)->toBeInstanceOf(Message::class)
        ->and($message->getPayload())->toBe('foobar')
        ->and($queue->countEnqueued())->toBe(1)
        ->and($message->getState())->toBe(Message\State::ENQUEUED);
    $empty = $queue->next();
    expect($empty)->toBeNull();
});

it('returns nothing (null) on empty queue', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    expect($queue->next())->toBeNull();
});

it('rewrites message\'s state to ENQUEUED', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message = new Message('foobar');
    $message->setState(Message\State::RUNNING);
    $queue->enqueue($message);
    expect($message->getState())->toBe(Message\State::ENQUEUED);
});

it('can load two messages and fetch them in correct order', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('1st message');
    $message2 = new Message('2nd message');
    $queue->enqueue($message1, $message2);
    expect($queue->countEnqueued())->toBe(2);

    $retrieved = $queue->next();
    expect($retrieved)->toBeInstanceOf(Message::class)
        ->and($retrieved->getPayload())->toBe('1st message')
        ->and($queue->countEnqueued())->toBe(2);

    $retrieved = $queue->next();
    expect($retrieved)->toBeInstanceOf(Message::class)
        ->and($retrieved->getPayload())->toBe('2nd message')
        ->and($queue->countEnqueued())->toBe(2);
});

it('dequeues message', function() {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('1st message');
    $message2 = new Message('2nd message');
    $queue->enqueue($message1, $message2);
    expect($queue->countEnqueued())->toBe(2);
    $result = $queue->dequeue($message1);
    expect($queue->countEnqueued())->toBe(1)->and($result)->toBeTrue();
    $result = $queue->dequeue($message2);
    expect($queue->countEnqueued())->toBe(0)->and($result)->toBeTrue();
    $result = $queue->dequeue($message1);
    expect($result)->toBeFalse();
});

it('clears enqueued messages', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('1st message');
    $message2 = new Message('2nd message');
    $queue->enqueue($message1, $message2);
    expect($queue->countEnqueued())->toBe(2);
    $queue->clear();
    expect($queue->countEnqueued())->toBe(0);
});

it('marks enqueued message as running', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message = new Message('foobar');
    $queue->enqueue($message);
    expect($queue->countEnqueued())->toBe(1)
        ->and($queue->isBusy())->toBeFalse();
    $queue->markRunning($message);
    expect($queue->countEnqueued())->toBe(0)
        ->and($queue->isBusy())->toBeTrue()
        ->and($message->getState())->toBe(Message\State::RUNNING);
});

it('does not allow to mark running message that does not exits', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('foobar');
    $message2 = new Message('lorem ipsum');
    $queue->enqueue($message1);
    $queue->markRunning($message2);
})->throws(QueueException::class, 'Unable to mark running a non-existent message');

it('does not allow more than one message to be run at the time', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('foobar');
    $message2 = new Message('lorem ipsum');
    $queue->enqueue($message1, $message2);
    $queue->markRunning($message1);
    $queue->markRunning($message2);
})->throws(QueueException::class, 'Only single message can be run at the time');

it('marks running message as done', function() {
    $queue = new FileStorage(getQueuesTmpDir());
    $message = new Message('foobar');
    $queue->enqueue($message);
    $queue->markRunning($message);
    expect($queue->isBusy())->toBeTrue();
    $queue->markDone($message);
    expect($queue->isBusy())->toBeFalse()
        ->and($queue->countDone())->toBe(1)
        ->and($message->getState())->toBe(Message\State::DONE);
});

it('makes sure that message must be set RUNNING before it may be set DONE', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message = new Message('foobar');
    $queue->enqueue($message);
    $queue->markDone($message);
})->throws(QueueException::class, 'Some message must be first marked RUNNING to be marked DONE afterwards');

it('does not allow to mark done message that does not exits', function () {
    $queue = new FileStorage(getQueuesTmpDir());
    $message1 = new Message('foobar');
    $message2 = Message::load('1', Message\State::RUNNING->name, 'lorem ipsum');
    $queue->enqueue($message1);
    $queue->markRunning($message1);
    $queue->markDone($message2);
})->throws(QueueException::class, 'Could not find given message in RUNNING state');
