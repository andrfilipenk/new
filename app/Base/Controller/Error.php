<?php
// app/Base/Controller/Error.php
namespace Base\Controller;

use Core\Mvc\Controller;

class Error extends Controller
{


    public function deniedAction()
    {
        $response = $this->getResponse();
        $response->setStatusCode(403);
        $this->getView()->setLayout('window');
        $content = $this->render('error/default', ['message' => 'access denied!']);
        $response->setContent($content);
        return $response->send();
    }

    public function notfoundAction()
    {
        $response = $this->getResponse();
        $response->setStatusCode(404);
        $response->setContent('Page not found');
        return $response->send();
    }
}