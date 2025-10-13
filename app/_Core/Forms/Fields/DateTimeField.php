<?php
/**
 * DateTimeField Class
 * 
 * Specialized composite field for date/time input with support for
 * timezone selection and various date/time format combinations.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

class DateTimeField extends CompositeField
{
    /**
     * @var string Field mode ('datetime', 'date', 'time')
     */
    private string $mode = 'datetime';

    /**
     * @var bool Whether to include timezone selector
     */
    private bool $includeTimezone = false;

    /**
     * @var bool Use separate date/time inputs instead of datetime-local
     */
    private bool $useSeparateInputs = false;

    /**
     * @var string|null Default timezone
     */
    private ?string $defaultTimezone = null;

    /**
     * @var array Available timezones
     */
    private array $timezones = [];

    /**
     * Create a new datetime field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     */
    public function __construct(string $name, array $config = [])
    {
        parent::__construct($name, array_merge($config, ['type' => 'datetime']));
        
        $this->mode = $config['mode'] ?? 'datetime';
        $this->includeTimezone = $config['include_timezone'] ?? false;
        $this->useSeparateInputs = $config['separate_inputs'] ?? false;
        $this->defaultTimezone = $config['default_timezone'] ?? date_default_timezone_get();
        $this->timezones = $config['timezones'] ?? $this->getCommonTimezones();
        
        $this->buildDateTimeFields();
        
        $this->setLabel($config['label'] ?? $this->getDefaultLabel());
        $this->setRenderMode('inline');
    }

    /**
     * Build date/time fields based on mode
     * 
     * @return void
     */
    private function buildDateTimeFields(): void
    {
        if ($this->useSeparateInputs) {
            $this->buildSeparateInputs();
        } else {
            $this->buildCombinedInput();
        }

        // Add timezone selector if enabled
        if ($this->includeTimezone) {
            $this->addTimezoneField();
        }
    }

    /**
     * Build combined datetime input
     * 
     * @return void
     */
    private function buildCombinedInput(): void
    {
        $inputType = match($this->mode) {
            'date' => 'date',
            'time' => 'time',
            default => 'datetime-local'
        };

        $field = new InputField('value', [
            'type' => $inputType,
            'label' => '',
            'required' => true
        ]);

        // Set min/max if provided
        if (isset($this->value['min'])) {
            $field->setAttribute('min', $this->value['min']);
        }
        if (isset($this->value['max'])) {
            $field->setAttribute('max', $this->value['max']);
        }

        $this->addField($field, 'value');
    }

    /**
     * Build separate date and time inputs
     * 
     * @return void
     */
    private function buildSeparateInputs(): void
    {
        if ($this->mode !== 'time') {
            $this->addField(
                InputField::date('date', [
                    'label' => 'Date',
                    'required' => true,
                    'placeholder' => 'YYYY-MM-DD'
                ])
            );
        }

        if ($this->mode !== 'date') {
            $this->addField(
                InputField::time('time', [
                    'label' => 'Time',
                    'required' => true,
                    'placeholder' => 'HH:MM'
                ])
            );
        }
    }

    /**
     * Add timezone field
     * 
     * @return void
     */
    private function addTimezoneField(): void
    {
        $this->addField(
            SelectField::create('timezone', $this->timezones, [
                'label' => 'Timezone',
                'required' => true,
                'value' => $this->defaultTimezone
            ])
        );
    }

    /**
     * Get default label based on mode
     * 
     * @return string
     */
    private function getDefaultLabel(): string
    {
        return match($this->mode) {
            'date' => 'Date',
            'time' => 'Time',
            default => 'Date & Time'
        };
    }

    /**
     * Get common timezones
     * 
     * @return array
     */
    private function getCommonTimezones(): array
    {
        return [
            ['UTC', 'UTC (Coordinated Universal Time)'],
            ['America/New_York', 'Eastern Time (US & Canada)'],
            ['America/Chicago', 'Central Time (US & Canada)'],
            ['America/Denver', 'Mountain Time (US & Canada)'],
            ['America/Los_Angeles', 'Pacific Time (US & Canada)'],
            ['America/Anchorage', 'Alaska Time'],
            ['Pacific/Honolulu', 'Hawaii Time'],
            ['Europe/London', 'London (GMT/BST)'],
            ['Europe/Paris', 'Paris (CET/CEST)'],
            ['Europe/Berlin', 'Berlin (CET/CEST)'],
            ['Europe/Rome', 'Rome (CET/CEST)'],
            ['Europe/Madrid', 'Madrid (CET/CEST)'],
            ['Europe/Moscow', 'Moscow (MSK)'],
            ['Asia/Dubai', 'Dubai (GST)'],
            ['Asia/Kolkata', 'India (IST)'],
            ['Asia/Bangkok', 'Bangkok (ICT)'],
            ['Asia/Singapore', 'Singapore (SGT)'],
            ['Asia/Hong_Kong', 'Hong Kong (HKT)'],
            ['Asia/Tokyo', 'Tokyo (JST)'],
            ['Australia/Sydney', 'Sydney (AEDT/AEST)'],
            ['Pacific/Auckland', 'Auckland (NZDT/NZST)']
        ];
    }

    /**
     * Get all PHP timezones
     * 
     * @return array
     */
    public static function getAllTimezones(): array
    {
        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $timezones[] = [$timezone, $timezone];
        }
        return $timezones;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        $value = parent::getValue();

        // If using combined input, return the value directly
        if (!$this->useSeparateInputs && isset($value['value'])) {
            return $value['value'];
        }

        // If using separate inputs, combine them
        if ($this->useSeparateInputs) {
            $combined = '';
            
            if (isset($value['date'])) {
                $combined = $value['date'];
            }
            
            if (isset($value['time'])) {
                $combined .= ($combined ? ' ' : '') . $value['time'];
            }
            
            return $combined ?: null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(mixed $value): self
    {
        if (is_string($value) && $this->useSeparateInputs) {
            // Split datetime string into components
            $datetime = new \DateTime($value);
            
            $separatedValue = [];
            
            if ($this->mode !== 'time') {
                $separatedValue['date'] = $datetime->format('Y-m-d');
            }
            
            if ($this->mode !== 'date') {
                $separatedValue['time'] = $datetime->format('H:i');
            }
            
            if ($this->includeTimezone) {
                $separatedValue['timezone'] = $datetime->getTimezone()->getName();
            }
            
            return parent::setValue($separatedValue);
        }

        return parent::setValue($value);
    }

    /**
     * Get DateTime object from field value
     * 
     * @return \DateTime|null
     */
    public function getDateTime(): ?\DateTime
    {
        $value = $this->getValue();
        
        if (empty($value)) {
            return null;
        }

        try {
            $timezone = null;
            
            if ($this->includeTimezone) {
                $values = parent::getValue();
                $timezoneStr = $values['timezone'] ?? $this->defaultTimezone;
                $timezone = new \DateTimeZone($timezoneStr);
            }
            
            return new \DateTime($value, $timezone);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set value from DateTime object
     * 
     * @param \DateTime $datetime DateTime object
     * @return self
     */
    public function setDateTime(\DateTime $datetime): self
    {
        $format = match($this->mode) {
            'date' => 'Y-m-d',
            'time' => 'H:i',
            default => 'Y-m-d\TH:i'
        };

        $value = $datetime->format($format);
        
        if ($this->includeTimezone) {
            $this->setValue([
                'value' => $value,
                'timezone' => $datetime->getTimezone()->getName()
            ]);
        } else {
            $this->setValue($value);
        }

        return $this;
    }

    /**
     * Static factory for date-only field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function dateOnly(string $name, array $config = []): self
    {
        $config['mode'] = 'date';
        return new self($name, $config);
    }

    /**
     * Static factory for time-only field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function timeOnly(string $name, array $config = []): self
    {
        $config['mode'] = 'time';
        return new self($name, $config);
    }

    /**
     * Static factory for datetime with timezone
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function withTimezone(string $name, array $config = []): self
    {
        $config['mode'] = 'datetime';
        $config['include_timezone'] = true;
        return new self($name, $config);
    }

    /**
     * Static factory for date range field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return CompositeField
     */
    public static function dateRange(string $name, array $config = []): CompositeField
    {
        $field = new CompositeField($name, array_merge($config, ['type' => 'daterange']));
        
        $field->addField(
            self::dateOnly('start', [
                'label' => 'Start Date',
                'required' => true
            ])
        );
        
        $field->addField(
            self::dateOnly('end', [
                'label' => 'End Date',
                'required' => true
            ])
        );
        
        $field->setLabel($config['label'] ?? 'Date Range');
        $field->setRenderMode('inline');
        $field->setFieldSeparator(' <span class="separator">to</span> ');
        
        return $field;
    }
}
