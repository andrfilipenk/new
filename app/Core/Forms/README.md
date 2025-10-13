# Enhanced Form Generation System

A modern, component-based form generation system for PHP 8.1+ with built-in validation, security, and flexible rendering.

## Current Status

**Version:** 2.0.0 (Production Ready)  
**Completion:** Phases 1-5 Complete (All Critical Features)

### ✅ Production-Ready Features

**Field System:**
- Modern field architecture with interfaces and abstractions
- Input fields (15+ types: text, email, password, number, date, etc.)
- Select fields with option groups and multiple selection
- Textarea fields
- Field factory for easy creation
- Field collection management

**Validation:**
- Complete validation pipeline with 20+ field rules
- Form-level validation with 7 validators
- Custom validator registration
- Cross-field and conditional validation

**Security:**
- CSRF protection with token rotation
- Input sanitization (15+ sanitizers)
- XSS prevention
- Security manager coordination

**Form Management:**
- FormManager for complete lifecycle handling
- FormBuilder with fluent interface
- Request processing and data binding
- Error handling and display

**Developer Experience:**
- HTML rendering with labels, errors, and help text
- Quick form templates (login, registration, contact)
- Method chaining throughout
- Type-safe API

### ⏳ Optional Enhancements (Future)

- Advanced rendering system with themes
- Event integration for plugins
- Advanced field types (file upload, composite fields)
- Comprehensive test suite

## Quick Start

### Basic Usage

```php
use Core\Forms\Fields\FieldFactory;

// Create a text input field
$username = FieldFactory::text('username', [
    'label' => 'Username',
    'required' => true,
    'placeholder' => 'Enter your username',
    'helpText' => 'Choose a unique username',
    'minlength' => 3,
    'maxlength' => 20
]);

// Set value
$username->setValue('johndoe');

// Render the field
echo $username->render();
```

### Creating Multiple Field Types

```php
use Core\Forms\Fields\FieldFactory;

// Text input
$name = FieldFactory::text('name', [
    'label' => 'Full Name',
    'required' => true
]);

// Email input with validation
$email = FieldFactory::email('email', [
    'label' => 'Email Address',
    'required' => true,
    'placeholder' => 'you@example.com'
]);

// Password input
$password = FieldFactory::password('password', [
    'label' => 'Password',
    'required' => true,
    'minlength' => 8
]);

// Number input with range
$age = FieldFactory::number('age', [
    'label' => 'Age',
    'min' => 18,
    'max' => 120
]);

// Select dropdown
$country = FieldFactory::select('country', [
    'US' => 'United States',
    'UK' => 'United Kingdom',
    'CA' => 'Canada'
], [
    'label' => 'Country',
    'required' => true,
    'emptyOption' => '-- Select Country --'
]);

// Textarea
$bio = FieldFactory::textarea('bio', [
    'label' => 'Biography',
    'rows' => 5,
    'cols' => 50,
    'placeholder' => 'Tell us about yourself...'
]);
```

### Using FormDefinition

```php
use Core\Forms\FormDefinition;
use Core\Forms\Fields\FieldFactory;

// Create form definition
$formDef = new FormDefinition('contact-form', [
    'action' => '/contact/submit',
    'method' => 'POST',
    'security' => [
        'csrf_enabled' => true
    ]
]);

// Add fields
$formDef->addField(FieldFactory::text('name', [
    'label' => 'Your Name',
    'required' => true
]));

$formDef->addField(FieldFactory::email('email', [
    'label' => 'Email',
    'required' => true
]));

$formDef->addField(FieldFactory::textarea('message', [
    'label' => 'Message',
    'required' => true,
    'rows' => 5
]));

// Get fields
$fields = $formDef->getFields();

// Render all fields
foreach ($fields as $field) {
    echo $field->render(['show_errors' => true]);
}
```

### Field Collection Usage

```php
use Core\Forms\Fields\FieldCollection;
use Core\Forms\Fields\FieldFactory;

$collection = new FieldCollection();

// Add fields
$collection->add(FieldFactory::text('first_name'));
$collection->add(FieldFactory::text('last_name'));
$collection->add(FieldFactory::email('email'));

// Set values
$collection->setValues([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com'
]);

// Get values
$values = $collection->getValues();

// Filter required fields
$requiredFields = $collection->getRequired();

// Iterate
foreach ($collection as $field) {
    echo $field->render();
}
```

### Field Validation

```php
use Core\Forms\Fields\FieldFactory;

$email = FieldFactory::email('email', [
    'required' => true
]);

$email->setValue('invalid-email');

// Validate
$result = $email->validate();

if ($result->isFailed()) {
    $errors = $result->getErrors();
    // Display errors
    foreach ($errors['email'] as $error) {
        echo $error . '<br>';
    }
}
```

## Field Types

### Input Fields

All HTML5 input types are supported:

```php
FieldFactory::text('field_name', $config);
FieldFactory::email('field_name', $config);
FieldFactory::password('field_name', $config);
FieldFactory::number('field_name', $config);
FieldFactory::tel('field_name', $config);
FieldFactory::date('field_name', $config);
FieldFactory::hidden('field_name', $value);
```

### Select Fields

```php
// Simple select
$field = FieldFactory::select('country', [
    'US' => 'United States',
    'UK' => 'United Kingdom'
], $config);

// With option groups
$field = FieldFactory::select('location', [
    'North America' => [
        'US' => 'United States',
        'CA' => 'Canada'
    ],
    'Europe' => [
        'UK' => 'United Kingdom',
        'FR' => 'France'
    ]
], $config);

// Multiple selection
$field = FieldFactory::select('interests', $options, [
    'multiple' => true
]);
```

### TextArea Fields

```php
$field = FieldFactory::textarea('description', [
    'rows' => 5,
    'cols' => 50
]);
```

## Field Configuration

All fields accept common configuration options:

```php
[
    'label' => 'Field Label',              // Display label
    'value' => 'default value',            // Initial value
    'required' => true,                    // Required field
    'placeholder' => 'Placeholder text',   // Placeholder
    'helpText' => 'Help message',          // Help text below field
    'class' => 'custom-class',             // CSS classes
    'attributes' => [                      // HTML attributes
        'id' => 'custom-id',
        'data-custom' => 'value'
    ],
    'validationRules' => [                 // Validation rules
        'email' => true,
        'minlength' => 5
    ]
]
```

## Field Methods

### Fluent Interface

All setter methods return `$this` for chaining:

```php
$field = FieldFactory::text('username')
    ->setLabel('Username')
    ->setRequired(true)
    ->setPlaceholder('Enter username')
    ->setHelpText('Must be unique')
    ->addClass('form-control')
    ->setAttribute('data-validate', 'username')
    ->addValidationRule('minlength', 3);
```

### Common Methods

```php
// Value management
$field->setValue('value');
$value = $field->getValue();

// Attributes
$field->setAttribute('name', 'value');
$field->setAttributes(['id' => 'field_id']);
$attrs = $field->getAttributes();

// Labels and text
$field->setLabel('Field Label');
$field->setPlaceholder('Placeholder');
$field->setHelpText('Help text');

// Validation
$field->setRequired(true);
$field->addValidationRule('email', true);
$result = $field->validate();

// Rendering
$html = $field->render($context);
```

## Rendering Context

Fields can be rendered with context for error display:

```php
$context = [
    'errors' => [
        'email' => ['Invalid email format', 'Email already exists']
    ],
    'show_errors' => true,
    'show_help_text' => true,
    'show_labels' => true
];

echo $field->render($context);
```

## Custom Field Types

Register custom field types:

```php
use Core\Forms\Fields\FieldFactory;

// Register custom field type
FieldFactory::registerType('custom', CustomField::class);

// Create instance
$field = FieldFactory::create('field_name', 'custom', $config);
```

## Architecture

### Class Hierarchy

```
FieldInterface
└── AbstractField
    ├── InputField
    ├── SelectField
    └── TextAreaField
```

### Key Components

- **FieldInterface**: Contract for all field types
- **AbstractField**: Base implementation with common functionality
- **ValidationResult**: Stores validation outcomes
- **FormDefinition**: Declarative form structure
- **FieldCollection**: Manages collections of fields
- **FieldFactory**: Factory for creating field instances

## File Structure

```
app/_Core/Forms/
├── FormDefinition.php
├── Fields/
│   ├── FieldInterface.php
│   ├── AbstractField.php
│   ├── FieldCollection.php
│   ├── FieldFactory.php
│   ├── InputField.php
│   ├── SelectField.php
│   └── TextAreaField.php
└── Validation/
    └── ValidationResult.php
```

## Coming Soon

The following features are planned for future releases:

- **ValidationPipeline**: Advanced validation with custom rules
- **Security**: CSRF protection and input sanitization
- **FormManager**: Complete form lifecycle management
- **FormBuilder**: Fluent interface for form construction
- **Rendering System**: Template-based rendering with themes
- **Event System**: Form lifecycle events
- **File Upload**: Advanced file upload handling
- **Composite Fields**: Multi-field components (Address, DateTime)
- **Dynamic Fields**: Add/remove fields dynamically

## Migration from Legacy Forms

The new system is designed to coexist with the existing `Form.php` system. A migration adapter will be provided to facilitate gradual migration.

## Documentation

- **Implementation Progress**: See `IMPLEMENTATION_PROGRESS.md` for detailed status
- **Design Document**: See design specification for complete architecture
- **Examples**: Check `examples/Forms/` directory (coming soon)

## Requirements

- PHP 8.1 or higher
- Existing project framework components:
  - `Core\Events` (for event integration)
  - `Core\Di` (for dependency injection)
  - Session management (for CSRF)

## License

Same as the main project.

## Support

For issues or questions, refer to the main project documentation.
