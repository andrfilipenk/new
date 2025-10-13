<?php
// app/Core/Utils/Tag.php
namespace Core\Utils;

class Tag
{
    private string $tag;
    private array $attributes = [];
    private array $content = [];
    private bool $escapeContent = true;
    
    private static array $voidTags = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 
        'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    public function __construct(string $tag)
    {
        $this->tag = strtolower($tag);
    }

    public function attr(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function attrs(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->attr($name, $value);
        }
        return $this;
    }

    public function content(...$content): self
    {
        $this->content = array_merge($this->content, $content);
        return $this;
    }

    public function rawContent(...$content): self
    {
        $this->escapeContent = false;
        $this->content = array_merge($this->content, $content);
        return $this;
    }

    public function render(): string
    {
        $html = '<' . $this->tag . $this->renderAttributes();
        
        if (in_array($this->tag, self::$voidTags)) {
            return $html . '>';
        }
        
        $content = $this->renderContent();
        return $html . '>' . $content . '</' . $this->tag . '>';
    }

    private function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }
        $attrs = [];
        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $attrs[] = $name;
            } elseif ($value !== false && $value !== null) {
                $attrs[] = $name . '="' . $this->escape((string)$value) . '"';
            }
        }
        
        return $attrs ? ' ' . implode(' ', $attrs) : '';
    }

    private function renderContent(): string
    {
        $content = '';
        foreach ($this->content as $item) {
            if (is_string($item)) {
                $content .= $this->escapeContent ? $this->escape($item) : $item;
            } elseif (is_object($item) && method_exists($item, '__toString')) {
                $content .= (string)$item;
            } elseif (is_array($item)) {
                $content .= $this->renderArrayContent($item);
            } else {
                $content .= (string)$item;
            }
        }
        
        return $content;
    }

    private function renderArrayContent(array $items): string
    {
        $content = '';
        foreach ($items as $item) {
            if (is_string($item)) {
                $content .= $this->escapeContent ? $this->escape($item) : $item;
            } elseif (is_object($item) && method_exists($item, '__toString')) {
                $content .= (string)$item;
            } else {
                $content .= (string)$item;
            }
        }
        return $content;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public static function __callStatic(string $name, array $args): self
    {
        $tag = new self($name);
        // Void tags should treat first argument as attributes.
        if (in_array($tag->tag, self::$voidTags)) {
            if (isset($args[0]) && is_array($args[0])) {
                $tag->attrs($args[0]);
            }
        } else {
            // Non-void tags: first argument is content, second is attributes
            if (isset($args[0])) {
                if (is_array($args[0])) {
                    $tag->content(...$args[0]);
                } else {
                    $tag->content($args[0]);
                }
            }
            if (isset($args[1]) && is_array($args[1])) {
                $tag->attrs($args[1]);
            }
        }
        return $tag;
    }

    public static function link(string $href, string $text, array $attrs = []): self
    {
        return self::a($text, array_merge(['href' => $href], $attrs));
    }

    public static function image(string $src, string $alt = '', array $attrs = []): self
    {
        return self::img(array_merge(['src' => $src, 'alt' => $alt], $attrs));
    }

    public static function stylesheet(string $href, array $attrs = []): self
    {
        return self::__callStatic('link', [])->attrs(array_merge([
            'rel'   => 'stylesheet',
            'href'  => $href,
            'type'  => 'text/css'
        ], $attrs));
    }

    public static function script(string $src, array $attrs = []): self
    {
        return self::__callStatic('script', [])->attrs(array_merge([
            'src' => $src
        ], $attrs));
    }

    public static function meta(string $name, string $content, array $attrs = []): self
    {
        return self::metaTag(array_merge([
            'name'      => $name,
            'content'   => $content
        ], $attrs));
    }

    public static function form(string $action, string $method = 'POST', array $attrs = []): self
    {
        return self::formTag(array_merge([
            'action' => $action,
            'method' => strtoupper($method)
        ], $attrs));
    }

    public static function csrfMetaTag(string $token): self
    {
        return self::meta('csrf-token', $token);
    }

    public static function csrfField(string $token): self
    {
        return self::input([
            'type'  => 'hidden', 
            'name'  => '_token', 
            'value' => $token
        ]);
    }

    public static function join(array $tags, string $separator = ''): string
    {
        $result = '';
        foreach ($tags as $tag) {
            if ($tag instanceof self) {
                $result .= $tag->render() . $separator;
            } else {
                $result .= (string)$tag . $separator;
            }
        }
        return $result;
    }

    public static function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    public static function unescape(string $content): string
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}