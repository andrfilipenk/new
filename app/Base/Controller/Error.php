<?php
// app/Base/Controller/Error.php
namespace Base\Controller;

use Core\Mvc\Controller;

class Error extends Controller
{
    public function deniedAction()
    {
        $this->getView()->setLayout('window');
        return $this->render('error/default', ['message' => 'access denied!']);
    }

    public function notfoundAction()
    {
        $this->getView()->setLayout('window');
        return $this->render('error/default', ['message' => 'Page not found!']);
    }

    public function errorAction()
    {
        $this->getView()->setLayout('window');
        return $this->render('error/default', ['message' => 'Application error!']);
    }
}