<?php

namespace Core\Utils;

class Tag 
{
    private string $tag;
    private array $attributes = [];
    private array $content = [];

    private static array $voidTags = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 
        'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    public function __construct(string $tag) 
    {
        $this->tag = $tag;
    }

    public function attr(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function content(...$content): self
    {
        $this->content = array_merge($this->content, $content);
        return $this;
    }

    public function render(): string
    {
        $html = '<' . $this->tag . $this->renderAttributes();
        
        if (in_array($this->tag, self::$voidTags)) {
            return $html . '>';
        }
        
        return $html . '>' . implode('', $this->content) . '</' . $this->tag . '>';
    }

    private function renderAttributes(): string
    {
        if (empty($this->attributes)) return '';
        
        $attrs = [];
        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $attrs[] = $name;
            } elseif ($value !== false && $value !== null) {
                $attrs[] = $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES) . '"';
            }
        }
        
        return $attrs ? ' ' . implode(' ', $attrs) : '';
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public static function __callStatic(string $name, array $args): self
    {
        $tag = new self($name);
        
        if (isset($args[0])) {
            $content = is_array($args[0]) ? $args[0] : [$args[0]];
            $tag->content(...$content);
        }

        if (isset($args[1]) && is_array($args[1])) {
            foreach ($args[1] as $attr => $value) {
                $tag->attr($attr, $value);
            }
        }

        return $tag;
    }
}