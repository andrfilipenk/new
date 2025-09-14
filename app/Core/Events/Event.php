<?php

namespace Core\Events;

class Event implements StoppableEventInterface
{
    protected $name;
    protected $data;
    protected $stopped = false;

    public function __construct(string $name, $data = null)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}