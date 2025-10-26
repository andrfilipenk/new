<?php

namespace Core\Events;

/**
 * Event Dispatcher
 * 
 * Simple event system for decoupling components
 */
class EventDispatcher
{
    protected static array $listeners = [];

    /**
     * Register event listener
     */
    public static function listen(string $event, callable $listener, int $priority = 0): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }

        self::$listeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority (higher first)
        usort(self::$listeners[$event], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Fire event
     */
    public static function fire(string $event, ...$payload): void
    {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $item) {
            $listener = $item['listener'];
            $listener(...$payload);
        }
    }

    /**
     * Check if event has listeners
     */
    public static function hasListeners(string $event): bool
    {
        return isset(self::$listeners[$event]) && !empty(self::$listeners[$event]);
    }

    /**
     * Remove all listeners for event
     */
    public static function forget(string $event): void
    {
        unset(self::$listeners[$event]);
    }

    /**
     * Clear all listeners
     */
    public static function flush(): void
    {
        self::$listeners = [];
    }

    /**
     * Get all listeners for event
     */
    public static function getListeners(string $event): array
    {
        return self::$listeners[$event] ?? [];
    }
}
