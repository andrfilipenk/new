<?php
// app/Core/Mvc/ViewInterface.php
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

// example to setup custom view template engine
// $view = new \Core\View\YourCustomViewEngine($config['view']);
// $application->setView($view);
// $application->run();
// Note: You need to implement the actual view rendering logic in YourCustomViewEngine class.
// The above is just an interface definition.
// The actual view rendering logic would depend on your chosen templating engine or custom implementation.