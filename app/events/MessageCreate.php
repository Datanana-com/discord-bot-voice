<?php

declare(strict_types=1);

namespace App\Events;

use App\EventAbstract;
use Discord\Parts\Channel\Message;

class MessageCreate extends EventAbstract
{
    protected ?array $nonTerminatableFunctions = [
        'test'
    ];

    protected ?array $terminatableFunctions = [];

    public function __construct()
    {
        
        echo 'asd' . PHP_EOL;
    }

    public function test(Message $message = null)
    {
        #syslog(LOG_INFO, 'Message: ' . $message->content);
        echo 'testing' . PHP_EOL;
        #echo $message->content . PHP_EOL;
    }
}
