<?php

declare(strict_types=1);

namespace App\Events;

use Exception;
use App\Exceptions\EventFunctionNotFoundException;

abstract class EventAbstract
{
    /**
     * Terminatable functions, which allow the event to be terminated.
     *
     * @var null|array[string]
     */
    protected ?array $terminatableFunctions;

    /**
     * All of the functions that are within this array will be called, before the event is terminated.
     *
     * @var null|array[string]
     */
    protected ?array $nonTerminatableFunctions;

    /**
     * The event that is being handled.
     *
     * @var object
     */
    public $eventData;

    /**
     * Retrieves the list of functions that will be called when the event is called, but will not terminate the request.
     *
     * @return null|array[string]
     */
    public function getTerminatableFunctions(): ?array
    {
        return $this->terminatableFunctions;
    }

    /**
     * Retrieves the list of functions that will be called when the event is called, but will not terminate the request.
     *
     * @return null|array[string]
     */
    public function getNonTerminatableFunctions(): ?array
    {
        return $this->nonTerminatableFunctions;
    }

    /**
     * Handles the order of the events.
     *
     * @return bool|void
     */
    public function handle($eventData)
    {
        $this->eventData = $eventData;

        $this->handleNonTerminatables();
        if ($this->handleTerminatables()) {
            return true;
        }
    }

    /**
     * Handles the non terminatable functions.
     *
     * @return void|false Returns false if an exception was thrown & void on success.
     */
    public function handleNonTerminatables()
    {
        foreach ($this->nonTerminatableFunctions ?? [] as $function) {
            try {
                if (method_exists($this, $function)) {
                    $this->$function($this->eventData);
                } else {
                    throw new EventFunctionNotFoundException("The function $function does not exist.");
                }
            } catch (Exception $e) {
                // Should log the exception
                return false;
            }

            // Should log the success of the event
        }
    }

    /**
     * Handles the terminatable functions.
     * The return value of the function will determine if the event should be terminated.
     * 1. If the function returns `true` or `throws an exception`, the event will be terminated.
     * 2. If the function returns `false` or `null` or `void`, the event will not be terminated.
     *
     * @return bool|void True on success and should terminate the event, false on exception and should terminate the event, void on success and should not terminate the event.
     */
    public function handleTerminatables()
    {
        foreach ($this->terminatableFunctions ?? [] as $function) {
            try {
                if (method_exists($this, $function)) {
                    // Should log the event that is going to be processed
                    $returnValue = $this->$function($this->eventData);

                    if ($returnValue === true) {
                        // Should log the event that was processed
                        return true;
                    } elseif ($returnValue === false) {
                        // Should log the event that wasn't processed
                        return false;
                    }
                } else {
                    // Should log the event that wasn't processed
                    throw new EventFunctionNotFoundException("The function $function does not exist.");
                }
            } catch (Exception $e) {
                // Should log the exception
                return true;
            }

            // Should log the non terminatable event that was processed
        }
    }

}