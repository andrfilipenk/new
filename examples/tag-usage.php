<?php
use Core\Utils\Tag;

// Basic usage
echo Tag::div('Hello World', ['class' => 'container']);
// <div class="container">Hello World</div>

// Multiple attributes
echo Tag::input([
    'type' => 'text',
    'name' => 'username',
    'placeholder' => 'Enter username'
]);

// Raw content
echo Tag::div()->rawContent('<strong>Bold text</strong>');
// <div><strong>Bold text</strong></div>

// Helper methods
echo Tag::link('/about', 'About Us', ['class' => 'nav-link']);
// <a href="/about" class="nav-link">About Us</a>

// CSRF protection
echo Tag::csrfMetaTag($token);
// <meta name="csrf-token" content="...">

echo Tag::csrfField($token);
// <input type="hidden" name="_token" value="...">

// Joining tags
$tags = [
    Tag::li('Item 1'),
    Tag::li('Item 2'),
    Tag::li('Item 3')
];
echo Tag::ul(Tag::join($tags));
// <ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>


// This will now correctly generate:
// <link rel="stylesheet" href="/css/app.css" type="text/css">
echo Tag::stylesheet('/css/app.css');

// With additional attributes:
echo Tag::stylesheet('/css/app.css', [
    'media' => 'screen',
    'integrity' => 'sha256-hash'
]);
