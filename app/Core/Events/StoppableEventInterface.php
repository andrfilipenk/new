<?php
// app/Core/Events/StoppableEventInterface.php
namespace Core\Events;

/**
 * PSR-14 compatible Stoppable Event Interface
 */
interface StoppableEventInterface
{
    /**
     * Is propagation stopped?
     *
     * This will typically only be used by the Dispatcher to determine if it should
     * stop propagating the event to subsequent listeners.
     *
     * @return bool
     *   True if the Event is no longer "live" or False otherwise.
     */
    public function isPropagationStopped(): bool;
}