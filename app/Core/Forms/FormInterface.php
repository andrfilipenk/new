<?php

namespace Core\Forms;

interface FormInterface
{
    public function addField(string $name, string $type, array $options = []): FormInterface;
    public function setValues(array $values): FormInterface;
    public function getValues(): array;
    public function render(): string;
    public function renderField(string $name): string;
    public function setTemplate(string $template): FormInterface;
    public function setFieldTemplate(string $fieldTemplate): FormInterface;
}