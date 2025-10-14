<?php

namespace Core\Calendar\Svg;

/**
 * Represents an SVG element with attributes and children
 */
class SvgElement
{
    private string $tagName;
    private array $attributes = [];
    private array $children = [];
    private ?string $textContent = null;

    public function __construct(string $tagName, array $attributes = [])
    {
        $this->tagName = $tagName;
        $this->attributes = $attributes;
    }

    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function appendChild(SvgElement $child): self
    {
        $this->children[] = $child;
        return $this;
    }

    public function setTextContent(string $content): self
    {
        $this->textContent = $content;
        return $this;
    }

    public function render(int $indent = 0): string
    {
        $indentStr = str_repeat('  ', $indent);
        $attrs = $this->renderAttributes();
        
        if (empty($this->children) && $this->textContent === null) {
            return "{$indentStr}<{$this->tagName}{$attrs} />";
        }
        
        $output = "{$indentStr}<{$this->tagName}{$attrs}>";
        
        if ($this->textContent !== null) {
            $output .= htmlspecialchars($this->textContent, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }
        
        if (!empty($this->children)) {
            $output .= "\n";
            foreach ($this->children as $child) {
                $output .= $child->render($indent + 1) . "\n";
            }
            $output .= $indentStr;
        }
        
        $output .= "</{$this->tagName}>";
        
        return $output;
    }

    private function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }
        
        $attrs = [];
        foreach ($this->attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $attrs[] = $name;
            } else {
                $escapedValue = htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $attrs[] = "{$name}=\"{$escapedValue}\"";
            }
        }
        
        return empty($attrs) ? '' : ' ' . implode(' ', $attrs);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
