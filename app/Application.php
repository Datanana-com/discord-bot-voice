<?php

declare(strict_types=1);

namespace App;

use Closure;
use App\Logs\Logger;
use Discord\Discord;
use ReflectionClass;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Events\EventAbstract;
use Discord\WebSockets\Event;
use App\Exceptions\EventNotFoundException;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Interaction;
use Discord\Repository\Interaction\GlobalCommandRepository;

class Application
{
    /**
     * @var \Discord\Discord
     */
    public Discord $discord;

    /**
     * Logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public LoggerInterface $log;

    /**
     * Allowed events from Discord instance.
     *
     * @var array
     */
    private array $allowedEvents = [];

    /**
     * Initializes the Application
     *
     * @param array $options
     */
    public function __construct(array $options, ?Closure $readyFunction = null)
    {
        $this->discord = new Discord($options + ['logger' => new Logger()]);
        $this->log = $this->discord->getLogger();

        // Retrieves every event name from the constants from the \Discord\WebSockets\Event class
        $this->allowedEvents = (new ReflectionClass(Event::class))->getConstants();

        $this->prepareEventClasses();

        // Handles the "on ready" bot event.
        $this->discord->on(
            'init',
            function (Discord $discord) use ($readyFunction) {
                $discord->getLogger()->info('Bot is ready!');

                if ($readyFunction !== null) {
                    $readyFunction($discord);
                }

                try {
                    $this->prepareCommandClasses();
                } catch (\Throwable $th) {
                    $discord->getLogger()->error('Error while preparing command classes: ' . $th->getMessage());
                    $discord->getLogger()->error('Error while preparing command classes: ' . $th->getTraceAsString());
                    $this->discord->close();
                }

            }
        );
    }

    /**
     * Starts the ReactPHP event loop.
     */
    public function run(): void
    {
        $this->discord->run();
    }

    /**
     * Retrieves the list of classes in a folder.
     *
     * @param string $folder
     * @return array
     */
    private function getClassesFromFolder(string $folder): array
    {
        $classes = scandir(__DIR__ . '/' . $folder);

        // Removes . and ..
        array_splice($classes, 0, 2);

        // Removes the .php
        return array_map(
            fn (string $class) => str_replace('.php', '', $class),
            $classes
        );
    }

    /**
     * Prepares the event classes.
     *
     * @return void
     */
    public function prepareEventClasses(): void
    {
        $events = $this->getClassesFromFolder('Events');

        foreach ($events as $event) {
            // Transforms the event class to the event name
            // e.g. MessageCreate => MESSAGE_CREATE
            $eventClass = $event;
            $eventName = strtoupper(snake($event));

            if (!in_array($eventName, $this->allowedEvents)) {
                throw new EventNotFoundException($event);
            }

            $this->handleEvent($eventName, "\\App\\Events\\$eventClass");
        }
    }

    /**
     * Handles a events.
     *
     * @param string $eventName
     * @param string $eventClass
     * @return void
     */
    public function handleEvent(string $eventName, string $eventClass): void
    {
        $parentClass = get_parent_class($eventClass);
        $parentMethods = [];
        if ($parentClass) {
            $parentMethods = get_class_methods($parentClass);
        }

        $childMethods = get_class_methods($eventClass);

        // Retrieves the childs methods by removing the parent methods
        $childMethodsToRun = array_diff($childMethods, $parentMethods);

        // Removes special methods
        $childMethodsToRun = array_filter(
            $childMethodsToRun,
            fn ($method) => str_contains($method, '__') === false
        );

        /**
         * @var \App\EventAbstract $eventClass
         */
        $this->discord->on(
            $eventName,
            function ($event, Discord $discord) use ($eventClass, $childMethodsToRun) {
                $eventHandlerClass = new $eventClass($event, $discord, $childMethodsToRun);
                $logger = $discord->getLogger();

                try {
                    // Executes the event's methods before the event handler
                    $logger->debug('Executing the event handler before the event class..');
                    if ($eventHandlerClass->before()) {
                        return true;
                    }

                    // Handles all of the functions within the event's class
                    $logger->debug('Executing the event handler..');
                    $eventHandlerClass->handle();
                } catch (\Exception $e) {
                    $logger->error('Error while handling event: ' . $e->getMessage());
                    $logger->error('Trace' . $e->getTraceAsString());
                    return false;
                } finally {
                    // Executes the event's methods after the event handler
                    // To "fake" a middleware
                    $logger->debug('Executing the event handler after the event class..');
                    $eventHandlerClass->after();
                }
            }
        );
    }

    /**
     * Register the bot's slash commands
     *
     * @return void
     */
    public function prepareCommandClasses(): void
    {
        if (env('BOT_SLASH_COMMANDS', false) === false) {
            $this->log->info('Slash commands are disabled.');
            return;
        }

        $commands = $this->getClassesFromFolder('Commands');
        $globalCommands = [];
        $guildSpecificCommands = [];

        foreach ($commands as $command) {
            $commandClass = $command;
            $command = strtolower($command);
            if (str_contains($command, 'global')) {
                $globalCommands[] = $commandClass;
            } else {
                $guildSpecificCommands[] = $commandClass;
            }
        }

        if (!empty($globalCommands)) {
            $this->log->info('Global commands found: ' . implode(', ', $globalCommands));

            $this->handleGlobalCommands($globalCommands);
        }

        if (!empty($guildSpecificCommands)) {
            $this->log->info('Guild specific commands found: ' . implode(', ', $guildSpecificCommands));

            $this->handleGuildSpecificCommands($guildSpecificCommands);
        }
    }

    /**
     * Automates the handling of global commands by class.
     *
     * @param string $commandClass
     * @return void
     */
    public function handleGlobalCommands(array $globalCommandsClasses)
    {
        foreach ($globalCommandsClasses as $commandClass) {
            $commandName = Str::slug(strtolower(str_replace(['Global', 'Command'], '', $commandClass)));

            $commandClass = "\\App\\Commands\\{$commandClass}";
            $commandClass = new $commandClass($this->discord);
            $discordCommandClass = (new Command($this->discord))
                ->setName($commandName)
                ->setDescription($commandClass->description)
                ->setType($commandClass?->type ?? Command::CHAT_INPUT);

            // Check if the command already exists
            $availableCommands = $this->discord->application->commands;

            if (! in_array($commandName, $availableCommands->toArray())) {

                // Save the command to the Discord API
                $this->discord->application->commands->save($discordCommandClass)
                    ->finally(function () use ($commandName) {
                        $this->log->info("Command {$commandName} has been saved.");
                    });

                $this->discord->application->commands->freshen();
                $this->log->info('Something...');
            } else {
                $this->log->info("Command {$commandName} already exists.");
            }

            $this->discord->listenCommand($commandName, fn (Interaction $interaction) => $commandClass->handle($interaction));
        }
    }

    /**
     * TODO: Add a way to handle guild specific commands
     *
     * @param string $commandClass
     * @return void
     */
    public function handleGuildSpecificCommands(array $commandClass)
    {

    }

}