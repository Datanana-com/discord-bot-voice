<?php

declare(strict_types=1);

namespace App;

use Discord\Discord;
use App\Events\EventAbstract;
use Discord\WebSockets\Event;

abstract class Application
{


    /**
     * @var Discord
     */
    protected $discord;

    /**
     * Initializes the Application
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->discord = new Discord($options);

        $this->events = $this->getFolderClasses('events');
        $this->commands = $this->getFolderClasses('commands');

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

        foreach ($events as $event) {
            // Transforms the event class to the event name
            // e.g. MessageCreate => MESSAGE_CREATE
            $eventClass = $event;
            $eventName = strtoupper(snake($event));

            if (!in_array($event, $messageEvents)) {
                throw new EventNotFoundException($event);
            }

            /**
             * @var EventAbstract $eventClassObject
             */
            $eventClassObject = new $eventClass();

            $eventClassObject->getTerminatableFunctions();
            $eventClassObject->getNonTerminatableFunctions();
        }
    }
    

    public function getAvailableMessageEvents()
    {
        $this->allowed
    }
}
