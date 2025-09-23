<?php
// app/Module/Base/Controller/Error.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Error extends Controller
{

    public function deniedAction()
    {
        $response = $this->getResponse();
        $response->setStatusCode(403);
        $response->setContent('Access denied');
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