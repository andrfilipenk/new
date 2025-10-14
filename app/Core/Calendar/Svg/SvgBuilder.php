<?php

namespace Core\Calendar\Svg;

/**
 * Fluent builder for creating SVG elements
 */
class SvgBuilder
{
    public static function rect(float $x, float $y, float $width, float $height, array $attrs = []): SvgElement
    {
        return new SvgElement('rect', array_merge([
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ], $attrs));
    }

    public static function circle(float $cx, float $cy, float $r, array $attrs = []): SvgElement
    {
        return new SvgElement('circle', array_merge([
            'cx' => $cx,
            'cy' => $cy,
            'r' => $r,
        ], $attrs));
    }

    public static function line(float $x1, float $y1, float $x2, float $y2, array $attrs = []): SvgElement
    {
        return new SvgElement('line', array_merge([
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
        ], $attrs));
    }

    public static function text(string $content, float $x, float $y, array $attrs = []): SvgElement
    {
        $element = new SvgElement('text', array_merge([
            'x' => $x,
            'y' => $y,
        ], $attrs));
        $element->setTextContent($content);
        return $element;
    }

    public static function group(array $attrs = []): SvgElement
    {
        return new SvgElement('g', $attrs);
    }

    public static function path(string $d, array $attrs = []): SvgElement
    {
        return new SvgElement('path', array_merge([
            'd' => $d,
        ], $attrs));
    }

    public static function polygon(array $points, array $attrs = []): SvgElement
    {
        $pointsStr = implode(' ', array_map(fn($p) => "{$p[0]},{$p[1]}", $points));
        return new SvgElement('polygon', array_merge([
            'points' => $pointsStr,
        ], $attrs));
    }

    public static function use(string $href, float $x = 0, float $y = 0, array $attrs = []): SvgElement
    {
        return new SvgElement('use', array_merge([
            'href' => $href,
            'x' => $x,
            'y' => $y,
        ], $attrs));
    }

    public static function linearGradient(string $id, array $stops): SvgElement
    {
        $gradient = new SvgElement('linearGradient', ['id' => $id]);
        foreach ($stops as $offset => $color) {
            $stop = new SvgElement('stop', [
                'offset' => $offset,
                'stop-color' => $color,
            ]);
            $gradient->appendChild($stop);
        }
        return $gradient;
    }
}
