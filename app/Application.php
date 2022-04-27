<?php

declare(strict_types=1);

namespace App;

use Discord\Discord;
use ReflectionClass;
use App\Events\EventAbstract;
use Discord\WebSockets\Event;
use App\Exceptions\EventNotFoundException;

class Application
{
    /**
     * @var Discord
     */
    public Discord $discord;

    /**
     * Initializes the Application
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->discord = new Discord($options);

        $this->prepareEventClasses();

        $this->discord->on(
            'ready',
            fn (Discord $discord) => $discord->getLogger()->info('Bot is ready!')
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
    private function getFolderClasses(string $folder): array
    {
        $events = scandir(__DIR__ . '/' . $folder);

        // Removes . and ..
        array_splice($events, 0, 2);

        // Removes the .php
        return array_map(
            fn ($event) => str_replace('.php', '', $event),
            $events
        );
    }

    /**
     * Prepares the event classes.
     *
     * @return void
     */
    public function prepareEventClasses(): void
    {
        $events = $this->getFolderClasses('events');

        // Retrieves every event name from the constants from the \Discord\WebSockets\Event class
        $allowedEvents = (new ReflectionClass(Event::class))->getConstants();

        foreach ($events as $event) {
            // Transforms the event class to the event name
            // e.g. MessageCreate => MESSAGE_CREATE
            $eventClass = $event;
            $eventName = strtoupper(snake($event));

            if (!in_array($eventName, $allowedEvents)) {
                throw new EventNotFoundException($event);
            }

            $this->handleEvents($eventName, "\\App\\Events\\$eventClass");
        }
    }

    /**
     * Handles the events.
     *
     * @param string $eventName
     * @param string $eventClass
     * @return void
     */
    public function handleEvents(string $eventName, string $eventClass): void
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
         * @var EventAbstract $eventClass
         */
        $this->discord->on(
            $eventName,
            fn ($event, Discord $discord) => (new $eventClass($event, $discord, $childMethodsToRun))->handle($event)
        );
    }
}
