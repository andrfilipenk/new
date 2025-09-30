<?php
// app/_Core/Mvc/Router/Route.php
namespace Core\Mvc\Router;

use stdClass;

class Route extends stdClass {
    
    protected $_matched = false;

    protected $_keys = [
        'module', 'controller', 'action', 'params'
    ];
    

    static public function fromArray(array $data)
    {
        $route = new self();
        foreach ($data as $key => $value) {
            $route->$key = $value;
        }
        return $route;
    }

    public function setMatched()
    {
        $this->_matched = true;
        return $this;
    }

    public function isMatched()
    {
        return $this->_matched;
    }

    
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setData($k, $v);
            }
        }
        if (is_string($key)) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * Returns result or false
     *
     * @return array|bool
     */
    public function getResult()
    {
        $result = [];
        foreach ($this->_keys as $key) {
            if (!isset($this->$key)) {
                return false;
            }
            $result[$key] = $this->$key;
        }
        return $result;
    }

    public function isMethod($method)
    {
        return in_array($method, $this->method);
    }
}