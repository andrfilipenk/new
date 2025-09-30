<?php
// app/_Core/Mvc/ViewInterface.php
namespace Core\Mvc;

interface ViewInterface
{
    public function render(string $template, array $data = []): string;
    public function partial(string $template, array $data = []): string;
    public function setLayout(string $layout): void;
    public function disableLayout(): void;
    public function enableLayout(): void;
    public function setVar(string $name, $value): void;
    public function setVars(array $data): void;
    public function getVar(string $name);
    public function startSection(string $name): void;
    public function endSection(): void;
    public function yield(string $name, string $default = ''): string;
}