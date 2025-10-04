<?php
// app/Api/Controller/UserController.php
namespace Api\Controller;

use Core\Mvc\Controller;
use Admin\Model\User;
use Core\Http\JsonResponse;

class UserController extends Controller
{
    public function indexAction()
    {
        try {
            $page = (int)($this->getRequest()->get('page', 1));
            $limit = min((int)($this->getRequest()->get('limit', 10)), 50);
            $search = $this->getRequest()->get('search', '');
            
            $query = User::with(['roles', 'groups']);
            
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
            }
            
            $total = $query->count();
            $users = $query->offset(($page - 1) * $limit)
                          ->limit($limit)
                          ->get();
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to fetch users', 500);
        }
    }
    
    public function showAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $user = User::with(['roles', 'groups', 'permissions'])->find($id);
        
        if (!$user) {
            return $this->jsonError('User not found', 404);
        }
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $user
        ]);
    }
    
    public function storeAction()
    {
        if (!$this->isPost()) {
            return $this->jsonError('Method not allowed', 405);
        }
        
        $validation = $this->validateRequest([
            'name' => ['required', ['min_length', 2]],
            'email' => ['required', 'email', ['unique', 'user', 'email']],
            'custom_id' => ['required', ['unique', 'user', 'custom_id']],
            'password' => ['required', ['min_length', 6]]
        ]);
        
        if (!$validation['valid']) {
            return $this->jsonError('Validation failed', 422, $validation['errors']);
        }
        
        try {
            $user = new User();
            $user->fill($this->getRequest()->all());
            
            if ($user->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => $user
                ], 201);
            }
            
            return $this->jsonError('Failed to create user', 500);
            
        } catch (\Exception $e) {
            return $this->jsonError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    public function updateAction()
    {
        if (!$this->getRequest()->isMethod('PUT') && !$this->getRequest()->isMethod('PATCH')) {
            return $this->jsonError('Method not allowed', 405);
        }
        
        $id = $this->getDispatcher()->getParam('id');
        $user = User::find($id);
        
        if (!$user) {
            return $this->jsonError('User not found', 404);
        }
        
        $validation = $this->validateRequest([
            'name' => [['min_length', 2]],
            'email' => ['email', ['unique', 'user', 'email', $id]],
            'custom_id' => [['unique', 'user', 'custom_id', $id]]
        ]);
        
        if (!$validation['valid']) {
            return $this->jsonError('Validation failed', 422, $validation['errors']);
        }
        
        try {
            $user->fill($this->getRequest()->all());
            
            if ($user->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => $user
                ]);
            }
            
            return $this->jsonError('Failed to update user', 500);
            
        } catch (\Exception $e) {
            return $this->jsonError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroyAction()
    {
        if (!$this->getRequest()->isMethod('DELETE')) {
            return $this->jsonError('Method not allowed', 405);
        }
        
        $id = $this->getDispatcher()->getParam('id');
        $user = User::find($id);
        
        if (!$user) {
            return $this->jsonError('User not found', 404);
        }
        
        try {
            // Remove relationships first
            $user->groups()->detach();
            $user->roles()->detach();
            
            if ($user->delete()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            }
            
            return $this->jsonError('Failed to delete user', 500);
            
        } catch (\Exception $e) {
            return $this->jsonError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    protected function jsonResponse(array $data, int $status = 200)
    {
        return $this->getResponse()->json($data, $status);
    }
    
    protected function jsonError(string $message, int $status = 400, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $this->getResponse()->json($response, $status);
    }
    
    protected function validateRequest(array $rules): array
    {
        $validator = $this->getDI()->get('validation');
        $data = $this->getRequest()->all();
        
        if ($validator->validate($data, $rules)) {
            return ['valid' => true];
        }
        
        return [
            'valid' => false,
            'errors' => $validator->getErrors()
        ];
    }
}