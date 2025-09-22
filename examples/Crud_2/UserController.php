<?php
// app/Module/Admin/Controllers/UserController.php
namespace Module\Admin\Controllers;

use Core\Crud\CrudController;
use Module\Admin\Services\UserCrudService;

class UserController extends CrudController
{
    protected function createCrudService(): UserCrudService
    {
        return new UserCrudService();
    }

    protected function getViewPath(): string
    {
        return 'admin/user';
    }

    protected function getRoutePrefix(): string
    {
        return '/admin/users';
    }
}