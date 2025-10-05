<?php
// app/_Core/Mvc/Router/Route.php
namespace Core\Mvc\Router;

use stdClass;

class Route extends stdClass {
    
    /**
     * Summary of _matched
     * 
     * @var bool
     */
    protected $_matched = false;

    /**
     * Summary of _keys
     * 
     * @var array
     */
    protected $_keys = [
        'module', 
        'controller', 
        'action', 
        'params'
    ];

    /**
     * Summary of fromArray
     * 
     * @param array $data
     * @return Route
     */
    static public function fromArray(array $data)
    {
        $route = new self();
        foreach ($data as $key => $value) {
            $route->$key = $value;
        }
        return $route;
    }

    /**
     * Summary of setMatched
     * 
     * @return static
     */
    public function setMatched()
    {
        $this->_matched = true;
        return $this;
    }

    /**
     * Summary of isMatched
     * 
     * @return bool
     */
    public function isMatched()
    {
        return $this->_matched;
    }
    
    /**
     * Summary of setData
     * 
     * @param mixed $key
     * @param mixed $value
     * @return static
     */
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

    /**
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return in_array($method, $this->method);
    }
}