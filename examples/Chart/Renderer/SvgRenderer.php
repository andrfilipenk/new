<?php
// app/Core/Chart/Renderer/SvgRenderer.php
namespace Core\Chart\Renderer;

/**
 * SVG rendering engine for charts
 * Following super-senior PHP practices with clean code and extensibility
 */
class SvgRenderer
{
    private int $width      = 800;
    private int $height     = 600;
    private string $background = '#ffffff';
    private array $elements = [];
    private array $styles   = [];
    private array $scripts  = [];

    public function setDimensions(int $width, int $height): self
    {
        $this->width    = $width;
        $this->height   = $height;
        return $this;
    }

    public function setBackground(string $color): self
    {
        $this->background = $color;
        return $this;
    }

    public function addElement(string $element): self
    {
        $this->elements[] = $element;
        return $this;
    }

    public function addRect(float $x, float $y, float $width, float $height, array $attributes = []): self
    {
        $attrs = $this->buildAttributes(array_merge([
            'x'         => $x,
            'y'         => $y,
            'width'     => $width,
            'height'    => $height
        ], $attributes));
        $this->elements[] = "<rect {$attrs} />";
        return $this;
    }

    public function addCircle(float $cx, float $cy, float $r, array $attributes = []): self
    {
        $attrs = $this->buildAttributes(array_merge([
            'cx'    => $cx,
            'cy'    => $cy,
            'r'     => $r
        ], $attributes));
        $this->elements[] = "<circle {$attrs} />";
        return $this;
    }

    public function addLine(float $x1, float $y1, float $x2, float $y2, array $attributes = []): self
    {
        $attrs = $this->buildAttributes(array_merge([
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2
        ], $attributes));
        $this->elements[] = "<line {$attrs} />";
        return $this;
    }

    public function addPath(string $d, array $attributes = []): self
    {
        $attrs = $this->buildAttributes(array_merge(['d' => $d], $attributes));
        $this->elements[] = "<path {$attrs} />";
        return $this;
    }

    public function addText(float $x, float $y, string $text, array $attributes = []): self
    {
        $attrs = $this->buildAttributes(array_merge([
            'x' => $x,
            'y' => $y
        ], $attributes));
        $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $this->elements[] = "<text {$attrs}>{$escapedText}</text>";
        return $this;
    }

    public function addPolygon(array $points, array $attributes = []): self
    {
        $pointsStr  = implode(' ', array_map(fn($p) => "{$p[0]},{$p[1]}", $points));
        $attrs      = $this->buildAttributes(array_merge(['points' => $pointsStr], $attributes));
        $this->elements[] = "<polygon {$attrs} />";
        return $this;
    }

    public function addGroup(array $elements, array $attributes = []): self
    {
        $attrs      = $this->buildAttributes($attributes);
        $content    = implode("\n    ", $elements);
        $this->elements[] = "<g {$attrs}>\n    {$content}\n</g>";
        return $this;
    }

    public function addStyle(string $css): self
    {
        $this->styles[] = $css;
        return $this;
    }

    public function addScript(string $js): self
    {
        $this->scripts[] = $js;
        return $this;
    }

    public function addGradient(string $id, array $stops, string $type = 'linear'): self
    {
        $stopElements = [];
        foreach ($stops as $stop) {
            $offset     = $stop['offset'] ?? 0;
            $color      = $stop['color'] ?? '#000000';
            $opacity    = isset($stop['opacity']) ? " stop-opacity=\"{$stop['opacity']}\"" : '';
            $stopElements[] = "<stop offset=\"{$offset}%\" stop-color=\"{$color}\"{$opacity} />";
        }
        $stopStr = implode("\n    ", $stopElements);
        if ($type === 'radial') {
            $gradient = "<radialGradient id=\"{$id}\">\n    {$stopStr}\n</radialGradient>";
        } else {
            $gradient = "<linearGradient id=\"{$id}\">\n    {$stopStr}\n</linearGradient>";
        }
        $this->elements[] = "<defs>{$gradient}</defs>";
        return $this;
    }

    public function addClipPath(string $id, string $path): self
    {
        $this->elements[] = "<defs><clipPath id=\"{$id}\">{$path}</clipPath></defs>";
        return $this;
    }

    public function render(): string
    {
        $svg = $this->buildSvgHeader();
        if (!empty($this->styles)) { // Add styles
            $styleContent = implode("\n", $this->styles);
            $svg .= "\n<style><![CDATA[\n{$styleContent}\n]]></style>";
        }
        if ($this->background !== 'transparent') { // Add background
            $svg .= "\n<rect width=\"{$this->width}\" height=\"{$this->height}\" fill=\"{$this->background}\" />";
        }
        $svg .= "\n" . implode("\n", $this->elements); // Add elements
        if (!empty($this->scripts)) { // Add scripts
            $scriptContent = implode("\n", $this->scripts);
            $svg .= "\n<script><![CDATA[\n{$scriptContent}\n]]></script>";
        }
        $svg .= "\n</svg>";
        return $svg;
    }

    private function buildSvgHeader(): string
    {
        return sprintf(
            '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">',
            $this->width,
            $this->height
        );
    }

    private function buildAttributes(array $attributes): string
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== '') {
                $escapedValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                $attrs[] = "{$key}=\"{$escapedValue}\"";
            }
        }
        return implode(' ', $attrs);
    }

    public function reset(): self
    {
        $this->elements = [];
        $this->styles = [];
        $this->scripts = [];
        return $this;
    }
}