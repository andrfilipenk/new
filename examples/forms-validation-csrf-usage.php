<?php
/**
 * Enhanced Forms with Validation and CSRF Protection - Usage Examples
 * 
 * This file demonstrates the new Phase 1 features:
 * - Server-side validation integration
 * - CSRF token protection
 * - Error display and handling
 * - Validation rules definition
 */

use Core\Forms\Builder;

// =============================================================================
// Example 1: Basic Form with Validation Rules
// =============================================================================

$loginForm = (new Builder())
    ->addEmail('email', 'Email Address', [
        'required' => true,
        'placeholder' => 'user@example.com',
        'rules' => ['required', 'email']
    ])
    ->addPassword('password', 'Password', [
        'required' => true,
        'rules' => ['required', ['min_length', 8]]
    ])
    ->addButton('submit', 'Login', ['type' => 'submit', 'class' => 'btn btn-primary'])
    ->build();

// The form automatically includes CSRF token
echo $loginForm->render();

// Output will include:
// <form method="post" action="">
//     <input type="hidden" name="_token" value="..." />
//     <div class="form-group">...</div>
// </form>


// =============================================================================
// Example 2: Form with Validation Errors
// =============================================================================

$registrationForm = (new Builder())
    ->addText('username', 'Username', [
        'required' => true,
        'rules' => ['required', ['min_length', 3], ['max_length', 20]]
    ])
    ->addEmail('email', 'Email', [
        'required' => true,
        'rules' => ['required', 'email', ['unique', 'users', 'email']]
    ])
    ->addPassword('password', 'Password', [
        'required' => true,
        'rules' => ['required', ['min_length', 8]]
    ])
    ->addPassword('password_confirm', 'Confirm Password', [
        'required' => true,
        'rules' => ['required', ['matches', 'password']]
    ])
    ->addButton('submit', 'Register', ['type' => 'submit', 'class' => 'btn btn-success'])
    ->build();

// After validation fails, set errors
$registrationForm->setErrors([
    'username' => ['Username is already taken'],
    'email' => ['Email is already registered', 'Invalid email format'],
    'password' => ['Password must contain at least one uppercase letter']
]);

echo $registrationForm->render();

// Output for fields with errors:
// <div class="form-group has-error">
//     <label for="email">Email</label>
//     <input type="email" name="email" id="email" ... />
//     <div class="field-errors">
//         <span class="error-message text-danger">Email is already registered</span>
//         <span class="error-message text-danger">Invalid email format</span>
//     </div>
// </div>


// =============================================================================
// Example 3: Controller Integration with Validation
// =============================================================================

class UserController extends Controller
{
    public function createAction()
    {
        $form = UserForm::build();
        
        if ($this->isPost()) {
            // Validate CSRF token first
            if (!$this->validateCsrfToken()) {
                $this->flashError('Invalid security token. Please try again.');
                return $this->render('user/form', ['form' => $form]);
            }
            
            $data = $this->getRequest()->all();
            
            // Get validation rules from the form
            $validator = $this->getValidator();
            
            try {
                // Validate data against form rules
                $validator->validate($data, $form->getValidationRules());
                
                // Validation passed - create user
                $user = new UserModel($data);
                if ($user->save()) {
                    $this->flashSuccess('User created successfully.');
                    return $this->redirect('user');
                }
            } catch (\Core\Exception\ValidationException $e) {
                // Validation failed - display errors on form
                $form->setErrors($e->getErrors());
            }
            
            // Repopulate form with submitted data
            $form->setValues($data);
        }
        
        return $this->render('user/form', ['form' => $form]);
    }
}


// =============================================================================
// Example 4: Disabling CSRF Protection (when needed)
// =============================================================================

$apiForm = (new Builder())
    ->addText('api_key', 'API Key')
    ->addButton('submit', 'Submit')
    ->build()
    ->enableCsrfProtection(false);  // Disable CSRF for API endpoints

echo $apiForm->render();


// =============================================================================
// Example 5: Complex Form with Multiple Validation Rules
// =============================================================================

$taskForm = (new Builder())
    ->addText('title', 'Task Title', [
        'required' => true,
        'rules' => ['required', ['min_length', 5], ['max_length', 100]]
    ])
    ->addSelect('priority', [
        '1' => 'Low',
        '2' => 'Medium',
        '3' => 'High',
        '4' => 'Critical'
    ], 'Priority', [
        'required' => true,
        'rules' => ['required', ['in', '1,2,3,4']]
    ])
    ->addDate('begin_date', 'Start Date', [
        'required' => true,
        'rules' => ['required', 'date']
    ])
    ->addDate('end_date', 'End Date', [
        'required' => true,
        'rules' => ['required', 'date', ['date_after', 'begin_date']]
    ])
    ->addTextarea('description', 'Description', [
        'rows' => 5,
        'rules' => [['max_length', 500]]
    ])
    ->addButton('submit', 'Create Task', ['type' => 'submit', 'class' => 'btn btn-primary'])
    ->build();


// =============================================================================
// Example 6: Custom Error Messages in Template
// =============================================================================

$customForm = (new Builder())
    ->addEmail('email', 'Email')
    ->addPassword('password', 'Password')
    ->setFieldTemplate('
        <div class="mb-3{error_class}">
            {label}
            {field}
            {error}
            <small class="form-text text-muted">Helper text here</small>
        </div>
    ')
    ->build();


// =============================================================================
// Example 7: Checking Form Validation Rules
// =============================================================================

$form = (new Builder())
    ->addText('username', 'Username', [
        'rules' => ['required', ['min_length', 3]]
    ])
    ->addEmail('email', 'Email', [
        'rules' => ['required', 'email']
    ])
    ->build();

// Get all validation rules for the form
$rules = $form->getValidationRules();
// Returns:
// [
//     'username' => ['required', ['min_length', 3]],
//     'email' => ['required', 'email']
// ]


// =============================================================================
// Example 8: Accessing Error Information
// =============================================================================

$form = (new Builder())
    ->addText('name', 'Name')
    ->addEmail('email', 'Email')
    ->build();

$form->setErrors([
    'name' => ['Name is required'],
    'email' => ['Email is invalid', 'Email already exists']
]);

// Check if form has errors
$allErrors = $form->getErrors();
// Returns: ['name' => ['Name is required'], 'email' => ['Email is invalid', 'Email already exists']]

// Check specific field
if ($form->hasError('email')) {
    echo "Email field has errors!";
}


// =============================================================================
// Example 9: Form in View Template (.phtml)
// =============================================================================

/*
In your controller:
    return $this->render('user/form', ['form' => $form]);

In your view (user/form.phtml):
    <div class="container">
        <h2>Create User</h2>
        
        <?php if ($form->getErrors()): ?>
            <div class="alert alert-danger">
                Please correct the errors below.
            </div>
        <?php endif; ?>
        
        <?php echo $form->render(); ?>
    </div>
*/


// =============================================================================
// Example 10: Dynamic User Options (without database in form)
// =============================================================================

// BEFORE (BAD - database query in form):
class OldTaskForm
{
    public static function build()
    {
        $users = User::all(); // Database query!
        // ...
    }
}

// AFTER (GOOD - pass data from controller):
class TaskForm
{
    public static function build(array $values = [], array $options = [])
    {
        $userOptions = $options['users'] ?? [];
        
        $builder = new Builder();
        $builder->addSelect('assigned_to', $userOptions, 'Assigned To', [
            'required' => true,
            'rules' => ['required', 'numeric']
        ]);
        
        return $builder->build();
    }
}

// In Controller:
class TaskController extends Controller
{
    public function createAction()
    {
        // Get users from service/repository
        $users = $this->getUserService()->getUsersForSelect();
        
        $form = TaskForm::build([], ['users' => $users]);
        
        return $this->render('task/create', ['form' => $form]);
    }
}


// =============================================================================
// Summary of New Features
// =============================================================================

/*
✅ CSRF Protection:
   - Automatically adds hidden _token field
   - Can be disabled with enableCsrfProtection(false)
   - Validated with Controller::validateCsrfToken()

✅ Validation Rules:
   - Define rules in form fields: 'rules' => ['required', 'email']
   - Access via $form->getValidationRules()
   - Integrate with existing Validator class

✅ Error Handling:
   - Set errors: $form->setErrors(['field' => ['error message']])
   - Get errors: $form->getErrors()
   - Check errors: $form->hasError('field')
   - Automatic error display in rendered fields

✅ Enhanced Field Template:
   - Supports {error} placeholder for error messages
   - Supports {error_class} for styling error states
   - Default: <div class="form-group{error_class}">{label}{field}{error}</div>

✅ Better Separation of Concerns:
   - No database queries in form classes
   - Pass data via $options parameter
   - Forms focus on rendering, not data fetching
*/
