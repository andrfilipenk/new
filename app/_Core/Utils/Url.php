<?php
// app/_Core/Utils/Url.php
namespace Core\Utils;

use Core\Di\Injectable;

class Url 
{
    use Injectable;

    protected $base_path = null;

    public function getBasePath() {
        if ($this->base_path === null) {
            $config = $this->getDI()->get('config');
            $this->base_path = $config['app']['base'];
        }
        return $this->base_path;
    }

    public function get($url, $params = [], $reset = false) {
        $url = $this->getBasePath() . $url;
        if ($reset) $_GET = [];
        $params = array_merge($_GET, $params);
        if (count($params) > 0) {
            foreach ($params as $k => $v) {
                if ($v === null || empty($v)) {
                    unset($params[$k]);
                }
            }
            if (count($params) > 0) {
                $url .= '?' . http_build_query($params);
            }
        }
        return $url;
    }
}