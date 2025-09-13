<?php
namespace Core\Utils;

class Tag {

    private $tag;
    private $attributes = [];
    private $content = [];
    private $selfClosing = false;

    private static $selfClosingTags = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 
        'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    public function __construct($tag) 
    {
        $this->tag = $tag;
        $this->selfClosing = in_array($tag, self::$selfClosingTags);
    }

    public function attr($name, $value) 
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function content(...$content) 
    {
        $this->content = array_merge($this->content, $content);
        return $this;
    }

    public function render() 
    {
        $html = '<' . $this->tag;
        
        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $html .= ' ' . $name;
            } else if ($value !== false && $value !== null) {
                $value = htmlspecialchars((string)$value, ENT_QUOTES);
                $html .= sprintf(" %s=\"%s\"", $name, $value);
            }
        }
        
        if ($this->selfClosing) {
            return $html . ' />';
        }
        
        $html .= '>';
        foreach ($this->content as $item) {
            $html .= (string)$item;
        }
        $html .= '</' . $this->tag . '>';
        
        return $html;
    }

    public function __toString() 
    {
        return $this->render();
    }

    public static function __callStatic($name, $arguments)
    {
        $el = new self($name);
        
        // First argument can be content
        if (isset($arguments[0]) && (is_string($arguments[0]) || is_array($arguments[0]) || $arguments[0] instanceof self)) {
            $el->content(...(is_array($arguments[0]) ? $arguments[0] : [$arguments[0]]));
        }

        // Second argument can be attributes
        $attributes = $arguments[1] ?? ($arguments[0] ?? []);
        if (is_array($attributes)) {
            foreach ($attributes as $k => $v) {
                $el->attr($k, $v);
            }
        }

        return $el;
    }
}

/*
// --- NEW USAGE EXAMPLES ---

// Simple tag with content and attributes
echo Tag::div('Hello World', ['class' => 'text-bold']);
// Output: <div class="text-bold">Hello World</div>

// Any self-closing tag is automatically handled
echo Tag::input(['type' => 'text', 'name' => 'username', 'required' => true]);
// Output: <input type="text" name="username" required />

// Create any tag dynamically
echo Tag::span('I am a span', ['id' => 'my-span']);
// Output: <span id="my-span">I am a span</span>

// Nesting tags
echo Tag::div([
    Tag::h1('Title'),
    Tag::p('This is a paragraph with a ' . Tag::strong('bold') . ' word.'),
    Tag::br(),
    Tag::input(['type' => 'submit', 'value' => 'Go'])
], ['class' => 'container']);
// Output: <div class="container"><h1>Title</h1><p>This is a paragraph with a <strong>bold</strong> word.</p><br /><input type="submit" value="Go" /></div>

*/