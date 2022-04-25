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
    public $discord;

    /**
     * Initializes the Application
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->discord = new Discord($options);

        $this->prepareEventClasses();
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

        $messageEvents = [
            Event::MESSAGE_CREATE,
            Event::MESSAGE_DELETE,
            Event::MESSAGE_UPDATE,
            Event::MESSAGE_DELETE_BULK,
            Event::MESSAGE_REACTION_ADD,
            Event::MESSAGE_REACTION_REMOVE,
            Event::MESSAGE_REACTION_REMOVE_ALL,
            Event::MESSAGE_REACTION_REMOVE_EMOJI,
        ];

        # Retrieves every event name from the constants from the \Discord\WebSockets\Event class
        $allowedEvents = (new ReflectionClass(Event::class))->getConstants();

        foreach ($events as $event) {
            if ($event === 'EventAbstract') {
                continue;
            }

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
    public function handleEvents($eventName, $eventClass)
    {
        $eventClass = new $eventClass();

        $this->discord->on($eventName, function ($object) use ($eventClass) {
            $eventClass->handle($object);
        });
    }
}
