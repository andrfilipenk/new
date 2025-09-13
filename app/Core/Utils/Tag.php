<?php
namespace Core\Utils;

class Tag {

    private $tag;
    private $attributes = [];
    private $content = '';
    private $selfClosing = false;

    public function __construct($tag, $selfClosing = false) 
    {
        $this->tag = $tag;
        $this->selfClosing = $selfClosing;
    }

    public function attr($name, $value) 
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function content($content) 
    {
        $this->content = $content;
        return $this;
    }

    public function render() 
    {
        $html = '<' . $this->tag;
        
        // Add attributes
        foreach ($this->attributes as $name => $value) {
            $value = htmlspecialchars($value, ENT_QUOTES);
            $html .= sprintf(" %s=\"%s\"", $name, $value);
        }
        
        // Self-closing tag or content
        if ($this->selfClosing) {
            $html .= ' />';
        } else {
            $html .= '>' . $this->content . '</' . $this->tag . '>';
        }
        
        return $html;
    }

    public function __toString() 
    {
        return $this->render();
    }

    // Quick static creators for common elements
    public static function div($content = '', $attributes = []) 
    {
        $el = new self('div');
        $el->content($content);
        foreach ($attributes as $k => $v) $el->attr($k, $v);
        return $el;
    }

    public static function label($label, $attributes = []) 
    {
        $el = new self('label');
        $el->content($label);
        foreach ($attributes as $k => $v) $el->attr($k, $v);
        return $el;
    }

    public static function input($attributes = []) 
    {
        $el = new self('input', true);
        foreach ($attributes as $k => $v) $el->attr($k, $v);
        return $el;
    }
}


/*

echo Tag::div('Hello World', ['class' => 'text-bold']);
// Output: <div class="text-bold">Hello World</div>

echo Tag::input(['type' => 'text', 'name' => 'username']);
// Output: <input type="text" name="username" />

 */