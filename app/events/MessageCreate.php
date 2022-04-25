<?php

declare(strict_types=1);

namespace App\Events;

use App\Events\EventAbstract;
use Discord\Parts\Channel\Message;

class MessageCreate extends EventAbstract
{
    protected ?array $nonTerminatableFunctions = [
        'test'
    ];

    protected ?array $terminatableFunctions = [];

    public function test(Message $message)
    {
        syslog(LOG_INFO, 'Message: ' . $message->content);
        echo 'testing' . PHP_EOL;
        echo $message->content . PHP_EOL;
    }
}
