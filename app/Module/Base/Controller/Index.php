<?php
// app/Module/Base/Controller/Index.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Index extends Controller
{
    public function indexAction(): void
    {
        /** @var \Core\Mvc\View $view */
        $view = $this->getDI()->get('view');
        $view->setLayout('app');

        
    }
    
    public function afterExecute(): void
    {
        // This is called after the action executes
        // You can modify the response here if needed
        parent::afterExecute();
    }
}