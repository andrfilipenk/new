<?php
// app/_Core/Acl/Access.php
namespace Core\Acl;

class Access 
{
    protected $_allowed = [];

    public function __construct($config = [])
    {
        $this->_allowed = $config['allowed'];
    }

    public function isAllowed($role, $module, $controller, $action)
    {
        $checkPath = implode('.', [$role, $module, $controller, $action]);
        foreach ($this->_allowed as $allowed) {
            if ($checkPath === $allowed) {
                return true;
            }
        }
        return false;
    }
}