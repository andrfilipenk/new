<?php
/**
 * Enhanced Form System - Advanced Features Demo
 * 
 * Demonstrates Phase 7 (Events) and Phase 8 (Advanced Fields) capabilities
 * 
 * @package Core\Forms
 * @since 2.0.0
 */

require_once __DIR__ . '/../autoload.php';

use Core\Forms\FormBuilder;
use Core\Forms\FormManager;
use Core\Forms\Fields\AddressField;
use Core\Forms\Fields\DateTimeField;
use Core\Forms\Fields\DynamicListField;
use Core\Forms\Fields\FileUploadField;
use Core\Forms\Events\FormEvent;
use Core\Forms\Events\FormEventDispatcher;

// ============================================================================
// EXAMPLE 1: Event System Integration
// ============================================================================

echo "<h2>Example 1: Form Events</h2>\n\n";

// Register event listeners
FormEventDispatcher::addListener(
    FormEvent::EVENT_FORM_CREATED,
    function(FormEvent $event) {
        echo "✓ Form '{$event->getFormName()}' was created\n";
    }
);

FormEventDispatcher::addListener(
    FormEvent::EVENT_FORM_VALIDATED,
    function(FormEvent $event) {
        $isValid = $event->get('is_valid');
        $formName = $event->getFormName();
        
        if ($isValid) {
            echo "✓ Form '{$formName}' validation passed\n";
        } else {
            echo "✗ Form '{$formName}' validation failed\n";
            echo "  Errors: " . json_encode($event->get('errors')) . "\n";
        }
    },
    10 // Higher priority - runs first
);

// Create a form (will trigger form.created event)
$simpleForm = FormBuilder::create('event_demo', ['action' => '/submit'])
    ->text('username', ['label' => 'Username', 'required' => true])
    ->email('email', ['label' => 'Email', 'required' => true])
    ->csrf()
    ->buildManager();

// Simulate form submission and validation
$simpleForm->handleRequest([
    'username' => 'johndoe',
    'email' => 'invalid-email' // Will fail validation
]);

echo "\n";

// ============================================================================
// EXAMPLE 2: Advanced Address Field
// ============================================================================

echo "<h2>Example 2: AddressField - Multiple Formats</h2>\n\n";

// US Address Format
$usAddress = AddressField::us('shipping_address', [
    'label' => 'Shipping Address (US)',
    'include_apartment' => true
]);

echo "US Address Fields:\n";
foreach ($usAddress->getFields() as $key => $field) {
    echo "  - {$key}: {$field->getLabel()}\n";
}
echo "\n";

// UK Address Format
$ukAddress = AddressField::uk('billing_address', [
    'label' => 'Billing Address (UK)',
    'include_apartment' => false
]);

echo "UK Address Fields:\n";
foreach ($ukAddress->getFields() as $key => $field) {
    echo "  - {$key}: {$field->getLabel()}\n";
}
echo "\n";

// International Address
$internationalAddress = AddressField::international('office_address', [
    'label' => 'Office Address',
    'countries' => [
        ['US', 'United States'],
        ['GB', 'United Kingdom'],
        ['CA', 'Canada'],
        ['AU', 'Australia']
    ]
]);

echo "International Address - Country options: ";
echo count($internationalAddress->getField('country')->getOptions()) . " countries\n\n";

// ============================================================================
// EXAMPLE 3: DateTime Field with Timezone
// ============================================================================

echo "<h2>Example 3: DateTimeField - Various Modes</h2>\n\n";

// Date only
$dateOnly = DateTimeField::dateOnly('birth_date', [
    'label' => 'Date of Birth'
]);

echo "Date Only Field: {$dateOnly->getType()}\n";

// Time only
$timeOnly = DateTimeField::timeOnly('meeting_time', [
    'label' => 'Meeting Time'
]);

echo "Time Only Field: {$timeOnly->getType()}\n";

// DateTime with timezone
$datetimeWithTz = DateTimeField::withTimezone('appointment', [
    'label' => 'Appointment',
    'default_timezone' => 'America/New_York'
]);

echo "DateTime with Timezone Fields:\n";
foreach ($datetimeWithTz->getFields() as $key => $field) {
    echo "  - {$key}: {$field->getLabel()}\n";
}

// Set a DateTime value
$now = new DateTime('2025-01-15 14:30:00', new DateTimeZone('America/Los_Angeles'));
$datetimeWithTz->setDateTime($now);

echo "\nSet DateTime: {$now->format('Y-m-d H:i:s T')}\n";
echo "Retrieved Value: {$datetimeWithTz->getValue()}\n\n";

// Date Range
$dateRange = DateTimeField::dateRange('vacation', [
    'label' => 'Vacation Period'
]);

echo "Date Range Field: {$dateRange->getLabel()}\n";
echo "  Subfields: " . implode(', ', array_keys($dateRange->getFields())) . "\n\n";

// ============================================================================
// EXAMPLE 4: Dynamic List Fields
// ============================================================================

echo "<h2>Example 4: DynamicListField - Add/Remove Items</h2>\n\n";

// Email list
$emailList = DynamicListField::emails('contact_emails', [
    'label' => 'Contact Email Addresses',
    'min_items' => 1,
    'max_items' => 5,
    'initial_items' => 2
]);

echo "Email List Configuration:\n";
echo "  - Min Items: 1\n";
echo "  - Max Items: 5\n";
echo "  - Initial Items: 2\n";
echo "  - Item Template: {$emailList->getItemTemplate()->getType()}\n\n";

// Phone list
$phoneList = DynamicListField::phones('phone_numbers', [
    'label' => 'Phone Numbers',
    'max_items' => 3
]);

echo "Phone List Template Type: {$phoneList->getItemTemplate()->getType()}\n\n";

// Custom dynamic list
$customList = DynamicListField::custom('links', 
    \Core\Forms\Fields\InputField::url('url', [
        'placeholder' => 'https://example.com',
        'required' => true
    ]),
    [
        'label' => 'Social Media Links',
        'max_items' => 10
    ]
);

echo "Custom List (URLs): {$customList->getLabel()}\n";
echo "  Max Items: 10\n\n";

// ============================================================================
// EXAMPLE 5: File Upload Field
// ============================================================================

echo "<h2>Example 5: FileUploadField - Advanced Validation</h2>\n\n";

// Image upload
$imageUpload = FileUploadField::image('profile_picture', [
    'label' => 'Profile Picture',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif']
]);

$imageUpload->setImageDimensions([
    'maxWidth' => 2000,
    'maxHeight' => 2000,
    'minWidth' => 200,
    'minHeight' => 200
]);

echo "Image Upload Configuration:\n";
echo "  - Max Size: " . ($imageUpload->getMaxFileSize() / 1024 / 1024) . "MB\n";
echo "  - Allowed Types: " . implode(', ', $imageUpload->getAllowedMimeTypes()) . "\n";
echo "  - Allowed Extensions: ." . implode(', .', $imageUpload->getAllowedExtensions()) . "\n\n";

// Document upload
$documentUpload = FileUploadField::document('resume', [
    'label' => 'Resume/CV',
    'max_file_size' => 10 * 1024 * 1024 // 10MB
]);

echo "Document Upload:\n";
echo "  - Allowed Types: " . count($documentUpload->getAllowedMimeTypes()) . " document types\n\n";

// Multiple file upload
$galleryUpload = FileUploadField::image('gallery', [
    'label' => 'Photo Gallery',
    'multiple' => true
]);

$galleryUpload->setMultiple(true, 10);

echo "Multiple Upload:\n";
echo "  - Multiple: " . ($galleryUpload->isMultiple() ? 'Yes' : 'No') . "\n";
echo "  - Max Files: 10\n\n";

// ============================================================================
// EXAMPLE 6: Complete Registration Form with Advanced Fields
// ============================================================================

echo "<h2>Example 6: Complete Registration Form</h2>\n\n";

$registrationForm = FormBuilder::create('advanced_registration', [
    'action' => '/register',
    'method' => 'POST'
])
    // Basic info
    ->text('username', [
        'label' => 'Username',
        'required' => true,
        'placeholder' => 'johndoe'
    ])
    ->email('email', [
        'label' => 'Email',
        'required' => true
    ])
    ->password('password', [
        'label' => 'Password',
        'required' => true
    ])
    
    // Address using AddressField
    ->addField(AddressField::us('address', [
        'label' => 'Home Address',
        'required' => true
    ]))
    
    // Profile picture
    ->addField(FileUploadField::image('avatar', [
        'label' => 'Profile Picture',
        'required' => false
    ]))
    
    // Contact emails (dynamic list)
    ->addField(DynamicListField::emails('additional_emails', [
        'label' => 'Additional Email Addresses',
        'min_items' => 0,
        'max_items' => 3,
        'initial_items' => 1
    ]))
    
    // Date of birth
    ->addField(DateTimeField::dateOnly('birth_date', [
        'label' => 'Date of Birth',
        'required' => true
    ]))
    
    // CSRF protection
    ->csrf()
    
    ->build();

echo "Advanced Registration Form Created:\n";
echo "  - Total Fields: " . count($registrationForm->getFields()) . "\n";
echo "  - Form Name: {$registrationForm->getName()}\n";
echo "  - CSRF Enabled: " . ($registrationForm->isCsrfEnabled() ? 'Yes' : 'No') . "\n\n";

echo "Field Breakdown:\n";
foreach ($registrationForm->getFields() as $field) {
    $type = $field->getType();
    $name = $field->getName();
    $label = $field->getLabel() ?: '(no label)';
    
    echo "  - [{$type}] {$name}: {$label}\n";
}

echo "\n";

// ============================================================================
// EXAMPLE 7: Event History Tracking
// ============================================================================

echo "<h2>Example 7: Event History Tracking</h2>\n\n";

// Enable event history
FormEventDispatcher::setTrackHistory(true);

// Create and process a form
$testForm = FormBuilder::create('history_test')
    ->text('test_field', ['required' => true])
    ->buildManager();

$testForm->handleRequest(['test_field' => 'test value']);

// Get event history
$history = FormEventDispatcher::getHistory();

echo "Event History (" . count($history) . " events):\n";
foreach ($history as $index => $event) {
    echo "  " . ($index + 1) . ". {$event['event']} - Form: {$event['form']}\n";
}

echo "\n";

// ============================================================================
// Summary
// ============================================================================

echo "=== SUMMARY ===\n\n";
echo "✓ Phase 7 (Events): FormEvent and FormEventDispatcher\n";
echo "✓ Phase 8 (Advanced Fields):\n";
echo "  - AddressField (US, UK, Canada, International formats)\n";
echo "  - DateTimeField (date, time, datetime modes with timezone)\n";
echo "  - DynamicListField (add/remove/reorder functionality)\n";
echo "  - FileUploadField (enhanced with image dimensions)\n\n";

echo "All features demonstrated successfully! ✅\n";
