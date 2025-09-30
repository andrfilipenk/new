<?php

use Core\Http\Request;
use Core\Utils\Dates;

// Integration with Form Processing

class MyClass {

    // In your controller or form processing logic
    public function processForm(Request $request)
    {
        $formData = $request->post();
        
        // Handle datetime fields
        if (!empty($formData['datetime_field'])) {
            try {
                $formData['datetime_field'] = new \DateTimeImmutable($formData['datetime_field']);
            } catch (\Exception $e) {
                // Handle invalid datetime
                $formData['datetime_field'] = null;
            }
        }
        
        // Handle date fields
        if (!empty($formData['date_field'])) {
            try {
                $formData['date_field'] = \DateTimeImmutable::createFromFormat('Y-m-d', $formData['date_field']);
            } catch (\Exception $e) {
                // Handle invalid date
                $formData['date_field'] = null;
            }
        }
        
        // Handle time fields
        if (!empty($formData['time_field'])) {
            try {
                $formData['time_field'] = \DateTimeImmutable::createFromFormat('H:i', $formData['time_field']);
            } catch (\Exception $e) {
                // Handle invalid time
                $formData['time_field'] = null;
            }
        }
        
        // Now you can use the processed data with DateTimeImmutable objects
        return $formData;
    }
}




// Usage Examples

// Creating a form with datetime fields
$form = (new Core\Forms\Builder())
    ->addTextField('title', 'Event Title')
    ->addDateTimeField('start_time', 'Start Time')
    ->addDateTimeField('end_time', 'End Time')
    ->addDateField('event_date', 'Event Date')
    ->setValues([
        'title' => 'My Event',
        'start_time' => new \DateTimeImmutable('2023-12-31 20:00:00'),
        'end_time' => new \DateTimeImmutable('2023-12-31 23:59:59'),
        'event_date' => new \DateTimeImmutable('2023-12-31')
    ])
    ->build();

echo $form->render();

// Processing form data
$processedData = $this->processForm($request);

// Using DateTimeImmutable in your business logic
if ($processedData['start_time'] instanceof \DateTimeImmutable) {
    $duration = $processedData['end_time']->diff($processedData['start_time']);
    echo "Event duration: " . $duration->format('%h hours %i minutes');
}

// Formatting for display in templates
echo Dates::humanReadable($processedData['start_time']);
echo Dates::createFromFormat($processedData['start_time'], 'F j, Y g:i a');





// Advanced: DateTime Validation Rule
// Add to your validation system
class DateTimeValidationRule
{
    public static function validate($value, string $format = null): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }
        
        if (!is_string($value)) {
            return false;
        }
        
        if ($format) {
            return Dates::isValidFormat($value, $format);
        }
        
        try {
            new \DateTimeImmutable($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function validateDate($value): bool
    {
        return self::validate($value, 'Y-m-d');
    }
    
    public static function validateTime($value): bool
    {
        return self::validate($value, 'H:i');
    }
    
    public static function validateDateTime($value): bool
    {
        return self::validate($value, 'Y-m-d\TH:i');
    }
}