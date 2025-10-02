<?php
// app/_Core/Config/Config.php
namespace Core\Config;

class Config
{
    protected array $config = [];

    static public function singleton($data = [])
    {
        return (new self)->set($data);
    }

    public function load(string $path)
    {
        $this->config = array_merge($this->config, require $path);
        return $this;
    }

    public function set($key, $value = null) 
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }
        $this->config[$key] = $value;
        return $this;
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}