<?php

namespace Core\Events;

class Manager
{
    protected $listeners = [];
    protected $sorted = [];

    public function attach(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
        unset($this->sorted[$event]);
    }

    public function detach(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $priority => $listeners) {
            foreach ($listeners as $key => $value) {
                if ($value === $listener) {
                    unset($this->listeners[$event][$priority][$key]);
                    unset($this->sorted[$event]);
                }
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

    public function trigger($event, $data = null)
    {
        if (is_string($event)) {
            $event = new Event($event, $data);
        }

        if (!$event instanceof EventInterface) {
            throw new \InvalidArgumentException('Event must be a string or implement EventInterface');
        }

        $eventName = $event->getName();
        
        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        // Sort listeners by priority if not already sorted
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }

        foreach ($this->sorted[$eventName] as $listener) {
            $result = call_user_func($listener, $event);
            #$result = $listener($event);
            
            if ($event->isPropagationStopped()) {
                break;
            }
            
            if ($result !== null) {
                $event->setData($result);
            }
        }

        return $event;
    }

    public function hasListeners(string $event = null): bool
    {
        if ($event === null) {
            return !empty($this->listeners);
        }

        return isset($this->listeners[$event]);
    }

    public function getListeners(string $event = null): array
    {
        if ($event === null) {
            return $this->listeners;
        }

        if (!isset($this->sorted[$event])) {
            $this->sortListeners($event);
        }

        return $this->sorted[$event] ?? [];
    }

    protected function sortListeners(string $event): void
    {
        $this->sorted[$event] = [];
        
        if (!isset($this->listeners[$event])) {
            return;
        }

        // Get all priorities and sort in reverse order (higher priority first)
        krsort($this->listeners[$event]);
        
        // Flatten the array
        foreach ($this->listeners[$event] as $listeners) {
            foreach ($listeners as $listener) {
                $this->sorted[$event][] = $listener;
            }
        }
    }
}