<?php
// app/Core/View/ViewInterface.php
namespace Core\View;

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
}