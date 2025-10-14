<?php
// app/Main/Controller/HomeController.php
namespace Main\Controller;

use Core\Mvc\Controller;

class HomeController extends Controller
{
    public function indexAction()
    {
        return $this->redirect('login')->send();
    }
}