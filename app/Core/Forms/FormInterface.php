<?php
// app/Core/Forms/FormInterface.php
namespace Core\Forms;

interface FormInterface
{
    public function addField(string $name, string $type, array $options = []): self;
    public function setValues(array $values): self;
    public function getValues(): array;
    public function render(): string;
    public function renderField(string $name): string;
    public function setTemplate(string $template): self;
    public function setFieldTemplate(string $fieldTemplate): self;
}