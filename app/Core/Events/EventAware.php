<?php
// app/Core/Events/EventAware.php
namespace Core\Events;

trait EventAware
{
    protected $eventsManager;

    public function setEventsManager(Manager $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    public function getEventsManager(): Manager
    {
        return $this->eventsManager;
    }

    public function fireEvent(string $eventName, $data = null): Event
    {
        if ($this->eventsManager) {
            return $this->eventsManager->trigger($eventName, $data);
        }
        return new Event($eventName, $data);
    }
}