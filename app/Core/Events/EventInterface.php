<?php

namespace Core\Events;

interface EventInterface
{
    public function getName(): string;
    public function getData();
    public function setData($data): void;
    public function stopPropagation(): void;
    public function isPropagationStopped(): bool;
}