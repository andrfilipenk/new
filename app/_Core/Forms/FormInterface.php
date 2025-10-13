<?php
// app/_Core/Forms/FormInterface.php
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
    public function setErrors(array $errors): self;
    public function getErrors(): array;
    public function hasError(string $field): bool;
    public function getValidationRules(): array;
    public function enableCsrfProtection(bool $enable = true): self;
}