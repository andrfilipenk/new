<?php
// app/Main/Controller/ErrorController.php
namespace Main\Controller;

use Core\Mvc\Controller;

class ErrorController extends Controller
{
    public function pageAction()
    {
        $e = $this->getDispatcher()->getParam('exception');
        if ($this->isAjax()) {
            return $this->getResponse()->json(['error' => $e->message], $e->statusCode);
        }
        
        $this->getView()->disableLayout();
        return $this->render('error', ['message' => 'access denied!', 'exception' => $e]);
    }
}