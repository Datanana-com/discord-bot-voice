<?php

declare(strict_types=1);

namespace App;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Psr\Log\LoggerInterface;

abstract class CommandAbstract
{
    /**
     * Guild ID in which to create the command.
     *
     * @var string|null
     */
    public ?string $guildId = null;

    /**
     * Description of the command.
     *
     * @var string
     */
    public string $description;

    public ?int $type = null;

    /**
     * The event's logger
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $log;

    public function __construct(
        public Discord $discord,
    ) {
        $this->log = $discord->getLogger();

        $this->log->info('Command initialized: ' . static::class);
    }

    abstract public function handle(Interaction $interaction): void;
}
