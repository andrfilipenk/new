<?php
namespace Core\UI;

use Core\Di\Injectable;
// app/Core/UI/Layout.php


class Layout {
    
    use Injectable;

    protected $_navItems = [];

    public function addNav($label, $icon, $url)
    {
        $helper = $this->getDI()->get('url');
        $this->_navItems[] = [
            'label' => $label,
            'icon' => 'bi bi-' . $icon,
            'active' => $this->isActive($url),
            'url' => $helper->get(substr($url, 1))
        ];
    }

    public function getNavItems()
    {
        return $this->_navItems;
    }

    protected function isActive($url)
    {
        $uri = $this->getDI()->get('request')->uri();
        return str_starts_with($uri,$url);
    }


    public function getMessages() {
        $session  = $this->getDI()->get('session');
        if ($session->has('messages')) {
            $messages = $session->get('messages');
            $session->remove('messages');
            return $messages;
        }
        return null;
    }
}