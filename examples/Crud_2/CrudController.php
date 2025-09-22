<?php
// app/Core/Crud/CrudController.php
namespace Core\Crud;

use Core\Mvc\Controller;

abstract class CrudController extends Controller
{
    use CrudControllerTrait;

    public function initialize(): void
    {
        #parent::initialize();
        
        $this->crudService = $this->createCrudService();
        $this->viewPath = $this->getViewPath();
        $this->routePrefix = $this->getRoutePrefix();
    }

    abstract protected function createCrudService(): CrudService;
    abstract protected function getViewPath(): string;
    abstract protected function getRoutePrefix(): string;
}