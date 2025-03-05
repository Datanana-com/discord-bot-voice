<?php

declare(strict_types=1);

namespace App\Logs;

use Carbon\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Monolog\Logger as Monolog;

class Logger extends Monolog implements LoggerInterface
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $filename = Carbon::now()->format('Y-m-d');

        parent::__construct(
            'DiscordPHP',
            [
                new StreamHandler('php://stdout', Level::Debug),
                new StreamHandler("logs/$filename.log", Level::Debug)
            ]
        );

        $this->debug('Logger initialized');
    }
}
