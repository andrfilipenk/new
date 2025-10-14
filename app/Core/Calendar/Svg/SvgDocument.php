<?php

namespace Core\Calendar\Svg;

/**
 * Main SVG document container
 */
class SvgDocument
{
    private int $width;
    private int $height;
    private array $attributes = [];
    private array $defs = [];
    private array $elements = [];
    private array $styles = [];

    public function __construct(int $width, int $height, array $attributes = [])
    {
        $this->width = $width;
        $this->height = $height;
        $this->attributes = $attributes;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function addDef(string $id, SvgElement $element): self
    {
        $this->defs[$id] = $element;
        return $this;
    }

    public function addElement(SvgElement $element): self
    {
        $this->elements[] = $element;
        return $this;
    }

    public function addStyle(string $css): self
    {
        $this->styles[] = $css;
        return $this;
    }

    public function render(): string
    {
        $attrs = array_merge([
            'xmlns' => 'http://www.w3.org/2000/svg',
            'width' => $this->width,
            'height' => $this->height,
            'viewBox' => "0 0 {$this->width} {$this->height}",
        ], $this->attributes);

        $svgAttrs = [];
        foreach ($attrs as $name => $value) {
            $escapedValue = htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $svgAttrs[] = "{$name}=\"{$escapedValue}\"";
        }

        $output = '<svg ' . implode(' ', $svgAttrs) . '>' . "\n";

        // Add styles
        if (!empty($this->styles)) {
            $output .= "  <style>\n";
            foreach ($this->styles as $style) {
                $output .= "    " . $style . "\n";
            }
            $output .= "  </style>\n";
        }

        // Add defs
        if (!empty($this->defs)) {
            $output .= "  <defs>\n";
            foreach ($this->defs as $def) {
                $output .= $def->render(2) . "\n";
            }
            $output .= "  </defs>\n";
        }

        // Add elements
        foreach ($this->elements as $element) {
            $output .= $element->render(1) . "\n";
        }

        $output .= '</svg>';

        return $output;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
