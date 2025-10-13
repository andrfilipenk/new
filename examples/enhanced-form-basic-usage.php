<?php
/**
 * Enhanced Form System - Basic Usage Example
 * 
 * This example demonstrates the current functionality of the 
 * Enhanced Form Generation System (Phases 1-2 Complete).
 * 
 * @package Examples
 */

// This would typically be included via autoloader
// require_once __DIR__ . '/../../app/bootstrap.php';

use Core\Forms\FormDefinition;
use Core\Forms\Fields\FieldFactory;
use Core\Forms\Fields\FieldCollection;

echo "<h1>Enhanced Form System - Basic Example</h1>";

echo "<hr><h2>1. Creating Individual Fields</h2>";

// Create various field types
$username = FieldFactory::text('username', [
    'label' => 'Username',
    'required' => true,
    'placeholder' => 'Enter your username',
    'helpText' => 'Choose a unique username (3-20 characters)',
    'minlength' => 3,
    'maxlength' => 20,
    'class' => 'form-control'
]);

echo $username->render();
echo "<br><br>";

$email = FieldFactory::email('email', [
    'label' => 'Email Address',
    'required' => true,
    'placeholder' => 'you@example.com',
    'helpText' => 'We will never share your email',
    'class' => 'form-control'
]);

echo $email->render();
echo "<br><br>";

$password = FieldFactory::password('password', [
    'label' => 'Password',
    'required' => true,
    'minlength' => 8,
    'helpText' => 'Must be at least 8 characters',
    'class' => 'form-control'
]);

echo $password->render();
echo "<br><br>";

$age = FieldFactory::number('age', [
    'label' => 'Age',
    'min' => 18,
    'max' => 120,
    'helpText' => 'You must be 18 or older',
    'class' => 'form-control'
]);

echo $age->render();
echo "<br><br>";

$country = FieldFactory::select('country', [
    'North America' => [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico'
    ],
    'Europe' => [
        'UK' => 'United Kingdom',
        'FR' => 'France',
        'DE' => 'Germany',
        'IT' => 'Italy',
        'ES' => 'Spain'
    ],
    'Asia' => [
        'JP' => 'Japan',
        'CN' => 'China',
        'IN' => 'India',
        'KR' => 'South Korea'
    ]
], [
    'label' => 'Country',
    'required' => true,
    'emptyOption' => '-- Select Your Country --',
    'class' => 'form-control'
]);

echo $country->render();
echo "<br><br>";

$bio = FieldFactory::textarea('bio', [
    'label' => 'Biography',
    'rows' => 5,
    'cols' => 50,
    'placeholder' => 'Tell us about yourself...',
    'helpText' => 'Maximum 500 characters',
    'maxlength' => 500,
    'class' => 'form-control'
]);

echo $bio->render();
echo "<br><br>";

echo "<hr><h2>2. Field Validation Example</h2>";

// Test email validation
$testEmail = FieldFactory::email('test_email', [
    'label' => 'Test Email',
    'required' => true
]);

// Invalid email
$testEmail->setValue('invalid-email-format');
$result = $testEmail->validate();

if ($result->isFailed()) {
    echo "<p style='color: red;'><strong>Validation Failed:</strong></p>";
    foreach ($result->getFlatErrors() as $error) {
        echo "<p style='color: red;'>- $error</p>";
    }
}

// Valid email
$testEmail->setValue('valid@example.com');
$result = $testEmail->validate();

if ($result->isValid()) {
    echo "<p style='color: green;'><strong>Validation Passed!</strong></p>";
}

echo "<hr><h2>3. Using FieldCollection</h2>";

$collection = new FieldCollection();

// Add fields to collection
$collection->add(FieldFactory::text('first_name', [
    'label' => 'First Name',
    'required' => true
]));

$collection->add(FieldFactory::text('last_name', [
    'label' => 'Last Name',
    'required' => true
]));

$collection->add(FieldFactory::email('contact_email', [
    'label' => 'Contact Email',
    'required' => true
]));

$collection->add(FieldFactory::tel('phone', [
    'label' => 'Phone Number'
]));

// Set values
$collection->setValues([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'contact_email' => 'john.doe@example.com',
    'phone' => '+1 234-567-8900'
]);

echo "<h3>Collection Fields:</h3>";
foreach ($collection as $field) {
    echo $field->render();
    echo "<br>";
}

echo "<p><strong>Field Count:</strong> " . $collection->count() . "</p>";
echo "<p><strong>Required Fields:</strong> " . count($collection->getRequired()) . "</p>";

echo "<h3>Current Values:</h3>";
echo "<pre>";
print_r($collection->getValues());
echo "</pre>";

echo "<hr><h2>4. Complete Form Definition Example</h2>";

$formDef = new FormDefinition('registration-form', [
    'action' => '/user/register',
    'method' => 'POST',
    'security' => [
        'csrf_enabled' => true
    ],
    'attributes' => [
        'class' => 'registration-form',
        'novalidate' => true
    ]
]);

// Add fields
$formDef->addField(FieldFactory::text('reg_username', [
    'label' => 'Username',
    'required' => true,
    'minlength' => 3,
    'maxlength' => 20
]));

$formDef->addField(FieldFactory::email('reg_email', [
    'label' => 'Email',
    'required' => true
]));

$formDef->addField(FieldFactory::password('reg_password', [
    'label' => 'Password',
    'required' => true,
    'minlength' => 8
]));

$formDef->addField(FieldFactory::password('password_confirm', [
    'label' => 'Confirm Password',
    'required' => true
]));

$formDef->addField(FieldFactory::select('reg_country', [
    'US' => 'United States',
    'UK' => 'United Kingdom',
    'CA' => 'Canada'
], [
    'label' => 'Country',
    'required' => true,
    'emptyOption' => '-- Select --'
]));

echo "<h3>Form Definition:</h3>";
echo "<p><strong>Name:</strong> " . $formDef->getName() . "</p>";
echo "<p><strong>Method:</strong> " . $formDef->getMethod() . "</p>";
echo "<p><strong>Action:</strong> " . $formDef->getAction() . "</p>";
echo "<p><strong>CSRF Enabled:</strong> " . ($formDef->isCsrfEnabled() ? 'Yes' : 'No') . "</p>";

$attrs = $formDef->getAttributes();
echo "<p><strong>Attributes:</strong></p>";
echo "<pre>";
print_r($attrs);
echo "</pre>";

echo "<h3>Form Fields:</h3>";
$fields = $formDef->getFields();
foreach ($fields as $field) {
    echo $field->render();
    echo "<br>";
}

echo "<hr><h2>5. Fluent Interface Example</h2>";

$fluentField = FieldFactory::text('fluent_example')
    ->setLabel('Fluent Example Field')
    ->setRequired(true)
    ->setPlaceholder('Type something...')
    ->setHelpText('This field was created using method chaining')
    ->addClass('form-control')
    ->addClass('custom-class')
    ->setAttribute('data-validate', 'custom')
    ->setDataAttribute('custom-attr', 'custom-value')
    ->addValidationRule('minlength', 5)
    ->setValue('Sample Value');

echo $fluentField->render();

echo "<hr><h2>6. Rendering with Error Context</h2>";

$errorContext = [
    'errors' => [
        'error_field' => [
            'This field is required.',
            'Value must be at least 5 characters.'
        ]
    ],
    'show_errors' => true,
    'show_help_text' => true
];

$errorField = FieldFactory::text('error_field', [
    'label' => 'Field with Errors',
    'required' => true,
    'helpText' => 'This demonstrates error display'
]);

echo $errorField->render($errorContext);

echo "<hr><h2>7. Multiple Select Example</h2>";

$interests = FieldFactory::select('interests', [
    'Programming' => 'Programming',
    'Design' => 'Design',
    'Marketing' => 'Marketing',
    'Sales' => 'Sales',
    'Management' => 'Management'
], [
    'label' => 'Interests',
    'multiple' => true,
    'helpText' => 'Select all that apply'
]);

$interests->setValue(['Programming', 'Design']);
echo $interests->render();

echo "<hr><h2>8. Custom Attributes Example</h2>";

$customField = FieldFactory::text('custom_attrs', [
    'label' => 'Custom Attributes Field',
    'attributes' => [
        'data-mask' => '999-999-9999',
        'data-validation' => 'phone',
        'autocomplete' => 'tel',
        'pattern' => '[0-9]{3}-[0-9]{3}-[0-9]{4}'
    ]
]);

echo $customField->render();

echo "<hr><p><em>Example complete. This demonstrates Phases 1-2 of the Enhanced Form System.</em></p>";

?>

<style>
/* Basic styling for the example */
.form-field {
    margin-bottom: 1.5rem;
}

.form-field label {
    display: block;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.form-field .required {
    color: red;
}

.form-field input[type="text"],
.form-field input[type="email"],
.form-field input[type="password"],
.form-field input[type="number"],
.form-field input[type="tel"],
.form-field select,
.form-field textarea {
    width: 100%;
    max-width: 500px;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.form-field .help-text {
    display: block;
    color: #666;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-field.has-error input,
.form-field.has-error select,
.form-field.has-error textarea {
    border-color: #dc3545;
}

.form-field .field-errors {
    margin-top: 0.5rem;
}

.form-field .field-errors .error {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
}

.form-control {
    /* Additional styling for form controls */
}
</style>
