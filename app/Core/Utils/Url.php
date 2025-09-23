<?php
// app/Core/Utils/Url.php
namespace Core\Utils;

use Core\Di\Injectable;

class Url 
{
    use Injectable;

    protected $base_path;

    public function __construct() {
        $config = $this->getDI()->get('config');
        $this->base_path = $config['app']['base'];
    }

    public function get($url) {
        return $this->base_path . $url;
    }
}