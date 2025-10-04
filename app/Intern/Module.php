<?php
// app/Intern/Module.php
namespace Intern;

use Core\Mvc\AbstractModule;

class Module extends AbstractModule 
{
    public function afterBootstrap($di)
    {
        parent::afterBootstrap($di);
        
        // Register events example
        #if ($di->has('eventsManager')) {
        #    $eventsManager = $di->get('eventsManager');
        #    $eventsManager->attach('navigation:build', [$this, 'registerNavigation']);
        #}
    }
}