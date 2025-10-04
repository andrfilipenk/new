<?php
// app/_Core/Events/Manager.php
namespace Core\Events;

use Core\Di\Injectable;
use InvalidArgumentException;

class Manager
{
    use Injectable;
    
    protected $listeners = [];
    protected $sorted = [];

    public function attach(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
        unset($this->sorted[$event]);
    }

    public function detach(string $event, callable $listener): void
    {
        if (empty($this->listeners[$event])) {
            return;
        }
        foreach ($this->listeners[$event] as $priority => &$listeners) {
            if (($key = array_search($listener, $listeners, true)) !== false) {
                unset($listeners[$key]);
                unset($this->sorted[$event]);
                return;
            }
        }
    }

    public function clearListeners(string $event = null): void
    {
        if ($event === null) {
            $this->listeners = [];
            $this->sorted = [];
        } else {
            unset($this->listeners[$event], $this->sorted[$event]);
        }
    }

    public function trigger($event, $data = null): Event
    {
        if (is_string($event)) {
            $event = new Event($event, $data);
        }
        if (!$event instanceof Event) {
            throw new InvalidArgumentException('Event must be a string or an instance of Core\Events\Event');
        }
        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }
        return $event;
    }

    public function getListenersForEvent(Event $event): iterable
    {
        $eventName = $event->getName();
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }
        $listeners = $this->sorted[$eventName] ?? [];
        // Add wildcard listeners
        foreach ($this->listeners as $eventPattern => $priorityListeners) {
            if (str_ends_with($eventPattern, '*') && str_starts_with($eventName, rtrim($eventPattern, '*'))) {
                if (!isset($this->sorted[$eventPattern])) {
                    $this->sortListeners($eventPattern);
                }
                $listeners = array_merge($listeners, $this->sorted[$eventPattern]);
            }
        }
        // Re-sort if wildcards were added
        if (count($listeners) > count($this->sorted[$eventName] ?? [])) {
            // This is a simplified sort for the combined list.
            // A more robust implementation would re-sort based on original priorities.
            return $listeners;
        }
        return $listeners;
    }

    protected function sortListeners(string $event): void
    {
        $this->sorted[$event] = [];
        if (empty($this->listeners[$event])) {
            return;
        }
        krsort($this->listeners[$event]);
        $this->sorted[$event] = array_merge(...array_values($this->listeners[$event]));
    }
}