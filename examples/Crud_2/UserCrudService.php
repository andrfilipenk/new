<?php
// app/Module/Admin/Services/UserCrudService.php
namespace Module\Admin\Services;

use Core\Crud\CrudService;
use Module\Admin\Models\Users;

class UserCrudService extends CrudService
{
    protected string $modelClass = Users::class;
    
    protected array $validationRules = [
        'name' => 'required|string|min:2|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'kuhnle_id' => 'required|integer|min:1|max:9999'
    ];
    
    protected array $searchableFields = ['name', 'email', 'kuhnle_id'];
    protected array $relationLoads = ['createdTasks', 'assignedTasks'];

    protected function afterCreate($model, array $data): void
    {
        // Hash password if present
        if (!empty($data['password'])) {
            $model->setData('password', password_hash($data['password'], PASSWORD_DEFAULT));
            $model->save();
        }
        
        // Log the creation
        #activity()->log("User {$model->name} created");
    }

    protected function afterUpdate($model, array $data): void
    {
        // Hash password if changed
        if (!empty($data['password'])) {
            $model->setData('password', password_hash($data['password'], PASSWORD_DEFAULT));
            $model->save();
        }
    }

    // Override validation rules for update
    protected function validate(array $data, $exceptId = null): void
    {
        $rules = $this->validationRules;
        
        // Make password optional for updates
        if ($exceptId !== null) {
            $rules['password'] = 'sometimes|min:8|confirmed';
            $rules['email'] = "required|email|unique:users,email,{$exceptId},user_id";
        }
        
        $validator = \Core\Validation\Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Core\Validation\ValidationException('Validation failed', $validator->errors());
        }
    }
}