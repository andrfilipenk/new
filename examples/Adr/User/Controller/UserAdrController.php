<?php
// examples/Adr/User/Controller/UserAdrController.php
namespace Examples\Adr\User\Controller;

use Core\Adr\AdrController;
use Examples\Adr\User\Action\CreateUserAction;
use Examples\Adr\User\Responder\UserResponder;

/**
 * User ADR Controller - Demonstrates ADR pattern integration
 */
class UserAdrController extends AdrController
{
    /**
     * Create user using ADR pattern
     */
    public function createUserAction()
    {
        // Option 1: Manual ADR execution
        return $this->adr(
            CreateUserAction::class,
            UserResponder::class
        );
    }

    /**
     * Alternative approach using auto-wiring
     */
    public function createAutoAction()
    {
        // Option 2: Auto-wired ADR (follows naming conventions)
        return $this->autoAdr('createUser');
    }

    /**
     * Index action - list users (traditional approach for comparison)
     */
    public function indexAction()
    {
        // You can still use traditional MVC approach when ADR isn't needed
        $users = $this->getDI()->get('userRepository')->findAll();
        
        return $this->render('user/index', ['users' => $users]);
    }

    /**
     * Show user details
     */
    public function showAction()
    {
        $id = $this->getDI()->get('dispatcher')->getParam('id');
        
        // For simple read operations, traditional approach might be sufficient
        $user = $this->getDI()->get('userRepository')->findById($id);
        
        if (!$user) {
            return $this->getResponse()->error('User not found', 404);
        }
        
        return $this->render('user/show', ['user' => $user]);
    }

    /**
     * Example of mixed approach - use ADR for complex operations
     */
    public function updateAction()
    {
        return $this->autoAdr('updateUser');
    }

    /**
     * Delete user with ADR
     */
    public function deleteAction()
    {
        return $this->autoAdr('deleteUser');
    }
}