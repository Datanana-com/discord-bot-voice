<?php

declare(strict_types=1);

namespace App;

use Exception;
use App\Exceptions\EventFunctionNotFoundException;

abstract class EventAbstract
{
    /**
     * Initializes the EventAbstract's data
     *
     * @param object $eventData The event object to handle data from
     * @param array $executableMethods The list of methods to execute
     */
    public function __construct(
        public object $eventData,
        private array $executableMethods = [],
    ) { }

    /**
     * Retrieves the class' methods that are executable by the event.
     * 
     * @return array
     */
    public function getExecutableMethods(): array
    {
        return $this->executableMethods;
    }

    /**
     * Sets the class' methods that are executable by the event.
     *
     * @param array $methods
     * @return self
     */
    public function setExecutableMethods(array $methods): self
    {
        $this->setExecutableMethods = $methods;

        return $this;
    }

    /**
     * Handles the order of the events.
     * The return value of the function will determine if the event should be terminated.
     * 1. If the function returns `true` or `throws an exception`, the event **will** be terminated.
     * 2. If the function returns `false` or `null` or `void`, the event **will not** be terminated.
     * 
     * @param array $class The event object to handle data from
     * @return bool|void
     */
    public function handle($class = null): ?bool
    {
        if ($class !== null && !isset($this->eventData)) {
            $this->eventData = $class;
        }

        if ($this->executableMethods === []) {
            // log warning - no executable methods were found
            return false;
        }

        foreach ($this->executableMethods as $method) {
            if (!method_exists($this, $method)) {
                throw new EventFunctionNotFoundException(
                    'The method ' . $method . ' does not exist.'
                );
            }

            try {
                if ($this->{$method}() === true) {
                    // If the method returns true, the event loop is terminated.
                    // log
                    return true;
                }
            } catch (Exception $e) {
                // log
                return false;
            }
        }
    }

}