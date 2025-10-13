<?php
// app/User/Module.php
namespace User;

use Core\Mvc\AbstractModule;

class Module extends AbstractModule 
{
    public function boot($di, $module, $controller, $action) {
        $em = $this->getDI()->get('eventsManager');
        $em->attach('dispatcher.beforeExecute', function($event) use($di, $module, $controller, $action) {
            $config = $di->get('config');
            $access = false;
            foreach ($config['acl']['public'] as $row) {
                list($m, $c, $a) = $row;
                if ($module === $m && $controller === $c && $action === $a) {
                    $access = true;
                    break;
                }
            }

            // get db access if logged in
            if (!$access) {
                $event->stopPropagation();
                #exit;
                return false;
            }
        });
    }
}