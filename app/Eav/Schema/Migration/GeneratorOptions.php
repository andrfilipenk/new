<?php

namespace App\Eav\Schema\Migration;

/**
 * Generator Options
 */
class GeneratorOptions
{
    private ?string $name;
    private bool $previewOnly;
    private string $template;

    public function __construct(
        ?string $name = null,
        bool $previewOnly = false,
        string $template = 'default'
    ) {
        $this->name = $name;
        $this->previewOnly = $previewOnly;
        $this->template = $template;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isPreviewOnly(): bool
    {
        return $this->previewOnly;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
