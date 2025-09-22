<?php
// app/Module/Admin/Controller/UserResourceController.php
namespace Module\Admin\Controller;

use Core\Mvc\CrudController;
use Module\Admin\Models\Users;
use Module\Admin\Forms\UserForm;
use Module\Admin\Services\UserService;

/**
 * Improved User controller using new CRUD system
 * Demonstrates super-senior PHP practices with service layer and validation
 */
class UserResourceController extends CrudController
{
    protected string $modelClass    = Users::class;
    protected string $formClass     = UserForm::class;
    protected string $serviceClass  = UserService::class;
    protected string $routePrefix   = '/admin/users';
    
    protected array $validationRules = [
        'name'      => 'required|string|min:2|max:100',
        'email'     => 'required|email|unique:users,email',
        'kuhnle_id' => 'required|numeric|min:1|max:9999|unique:users,kuhnle_id',
        'password'  => 'required|string|min:6'
    ];
    
    protected array $fillable = ['name', 'email', 'kuhnle_id', 'password'];
    protected array $with = []; // Add relations when implemented
    
    /**
     * Override to add search functionality
     */
    public function indexAction(): string|\Core\Http\Response
    {
        $search = $this->getRequest()->get('search');
        if ($search) {
            /** @var UserService $service */
            $service    = $this->getDI()->get($this->serviceClass);
            $users      = $service->searchUsers($search);
        } else {
            $users = Users::all();
        }
        if ($this->isJsonRequest()) {
            return $this->jsonResponse([
                'success'   => true,
                'data'      => $users
            ]);
        }
        return $this->render('index', [
            'users'     => $users,
            'search'    => $search
        ]);
    }
    
    /**
     * API endpoint for getting active users only
     */
    public function activeAction(): \Core\Http\Response
    {
        /** @var UserService $service */
        $service    = $this->getDI()->get($this->serviceClass);
        $users      = $service->getActiveUsers();
        return $this->jsonResponse([
            'success'   => true,
            'data'      => $users
        ]);
    }
}