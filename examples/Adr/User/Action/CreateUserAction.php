<?php
// examples/Adr/User/Action/CreateUserAction.php
namespace Examples\Adr\User\Action;

use Core\Adr\AbstractAction;
use Core\Http\Request;
use Examples\Adr\User\Dto\CreateUserDto;
use Examples\Adr\User\Domain\CreateUserDomain;

/**
 * Create User Action - Handles HTTP input for user creation
 */
class CreateUserAction extends AbstractAction
{
    public function __construct(CreateUserDomain $domain)
    {
        parent::__construct($domain);
    }

    /**
     * Create DTO from HTTP request
     */
    public function createDto(Request $request): object
    {
        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'kuhnle_id' => $request->input('kuhnle_id'),
            'roles' => $request->input('roles', ['user'])
        ];

        return CreateUserDto::fromArray($data);
    }

    /**
     * Get validation rules for HTTP input
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|max:255',
            'kuhnle_id' => 'nullable|integer',
            'roles' => 'nullable|array'
        ];
    }

    protected function getOperationName(): string
    {
        return 'CreateUser';
    }
}