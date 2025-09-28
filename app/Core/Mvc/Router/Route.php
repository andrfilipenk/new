<?php
namespace App\Core\Mvc\Router;

use stdClass;

class Route extends stdClass {

    static public function fromArray(array $data)
    {
        $route = new self();
        $route->matched = false;
        foreach ($data as $key => $value) {
            $route->$key = $value;
        }
        return $route;
    }

    public function setMatched()
    {
        $this->matched = true;
        return $this;
    }

    public function isMatched()
    {
        return $this->matched;
    }
}