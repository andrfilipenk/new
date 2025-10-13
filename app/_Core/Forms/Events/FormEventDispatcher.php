<?php
/**
 * FormEventDispatcher Class
 * 
 * Simple event dispatcher for form lifecycle events.
 * Allows listeners to hook into form creation, rendering, validation, and submission.
 * 
 * @package Core\Forms\Events
 * @since 2.0.0
 */

namespace Core\Forms\Events;

class FormEventDispatcher
{
    /**
     * @var array<string, array<callable>> Event listeners
     */
    private static array $listeners = [];

    /**
     * @var array Event history for debugging
     */
    private static array $eventHistory = [];

    /**
     * @var bool Whether to track event history
     */
    private static bool $trackHistory = false;

    /**
     * Add an event listener
     * 
     * @param string $eventName Event name to listen for
     * @param callable $listener Callback function
     * @param int $priority Priority (higher = earlier execution)
     * @return void
     */
    public static function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        if (!isset(self::$listeners[$eventName])) {
            self::$listeners[$eventName] = [];
        }

        self::$listeners[$eventName][] = [
            'callback' => $listener,
            'priority' => $priority
        ];

        // Sort by priority (descending)
        usort(self::$listeners[$eventName], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Remove an event listener
     * 
     * @param string $eventName Event name
     * @param callable $listener Callback to remove
     * @return bool Whether listener was found and removed
     */
    public static function removeListener(string $eventName, callable $listener): bool
    {
        if (!isset(self::$listeners[$eventName])) {
            return false;
        }

        $initialCount = count(self::$listeners[$eventName]);

        self::$listeners[$eventName] = array_filter(
            self::$listeners[$eventName],
            fn($item) => $item['callback'] !== $listener
        );

        return count(self::$listeners[$eventName]) < $initialCount;
    }

    /**
     * Check if event has listeners
     * 
     * @param string $eventName Event name
     * @return bool
     */
    public static function hasListeners(string $eventName): bool
    {
        return isset(self::$listeners[$eventName]) && !empty(self::$listeners[$eventName]);
    }

    /**
     * Get all listeners for an event
     * 
     * @param string $eventName Event name
     * @return array
     */
    public static function getListeners(string $eventName): array
    {
        return self::$listeners[$eventName] ?? [];
    }

    /**
     * Dispatch an event
     * 
     * @param FormEvent $event Event to dispatch
     * @return FormEvent The event (potentially modified by listeners)
     */
    public static function dispatch(FormEvent $event): FormEvent
    {
        $eventName = $event->getEventName();

        if (self::$trackHistory) {
            self::$eventHistory[] = [
                'event' => $eventName,
                'form' => $event->getFormName(),
                'timestamp' => $event->getTimestamp(),
                'data' => $event->getData()
            ];
        }

        if (!isset(self::$listeners[$eventName])) {
            return $event;
        }

        foreach (self::$listeners[$eventName] as $listenerData) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $callback = $listenerData['callback'];
            $callback($event);
        }

        return $event;
    }

    /**
     * Dispatch an event by name
     * 
     * @param string $eventName Event name
     * @param \Core\Forms\FormDefinition $form Form instance
     * @param array $data Event data
     * @return FormEvent
     */
    public static function dispatchEvent(
        string $eventName, 
        \Core\Forms\FormDefinition $form, 
        array $data = []
    ): FormEvent {
        $event = new FormEvent($eventName, $form, $data);
        return self::dispatch($event);
    }

    /**
     * Remove all listeners for an event
     * 
     * @param string $eventName Event name
     * @return void
     */
    public static function clearListeners(string $eventName): void
    {
        unset(self::$listeners[$eventName]);
    }

    /**
     * Remove all event listeners
     * 
     * @return void
     */
    public static function clearAllListeners(): void
    {
        self::$listeners = [];
    }

    /**
     * Enable event history tracking
     * 
     * @param bool $enable Whether to enable tracking
     * @return void
     */
    public static function setTrackHistory(bool $enable): void
    {
        self::$trackHistory = $enable;
    }

    /**
     * Get event history
     * 
     * @return array
     */
    public static function getHistory(): array
    {
        return self::$eventHistory;
    }

    /**
     * Clear event history
     * 
     * @return void
     */
    public static function clearHistory(): void
    {
        self::$eventHistory = [];
    }

    /**
     * Subscribe to multiple events at once
     * 
     * @param array $subscriptions Associative array of eventName => callback
     * @param int $priority Priority for all subscriptions
     * @return void
     */
    public static function subscribe(array $subscriptions, int $priority = 0): void
    {
        foreach ($subscriptions as $eventName => $callback) {
            self::addListener($eventName, $callback, $priority);
        }
    }

    /**
     * Get count of listeners for an event
     * 
     * @param string $eventName Event name
     * @return int
     */
    public static function getListenerCount(string $eventName): int
    {
        return count(self::$listeners[$eventName] ?? []);
    }

    /**
     * Get all registered event names
     * 
     * @return array
     */
    public static function getRegisteredEvents(): array
    {
        return array_keys(self::$listeners);
    }
}
