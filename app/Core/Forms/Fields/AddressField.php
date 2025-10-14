<?php
namespace Core\Forms\Fields;

class AddressField extends CompositeField
{
    /**
     * @var string Address format ('us', 'uk', 'international')
     */
    private string $format = 'international';

    /**
     * @var bool Whether to include apartment/unit field
     */
    private bool $includeApartment = true;

    /**
     * @var bool Whether to validate postal code format
     */
    private bool $validatePostalCode = true;

    /**
     * @var array Country list for dropdown
     */
    private array $countries = [];

    /**
     * Create a new address field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     */
    public function __construct(string $name, array $config = [])
    {
        parent::__construct($name, array_merge($config, ['type' => 'address']));
        
        // Set format
        $this->format = $config['format'] ?? 'international';
        $this->includeApartment = $config['include_apartment'] ?? true;
        $this->validatePostalCode = $config['validate_postal_code'] ?? true;
        $this->countries = $config['countries'] ?? $this->getDefaultCountries();
        
        // Build address fields based on format
        $this->buildAddressFields();
        
        $this->setLabel($config['label'] ?? 'Address');
    }

    /**
     * Build address fields based on format
     * 
     * @return void
     */
    private function buildAddressFields(): void
    {
        // Street address line 1
        $this->addField(
            InputField::text('street', [
                'label' => 'Street Address',
                'required' => true,
                'placeholder' => $this->getAddressPlaceholder('street'),
                'validationRules' => [
                    'minlength' => ['value' => 3, 'message' => 'Street address is too short']
                ]
            ])
        );

        // Apartment/Suite/Unit (optional)
        if ($this->includeApartment) {
            $this->addField(
                InputField::text('apartment', [
                    'label' => $this->format === 'uk' ? 'Flat/Unit' : 'Apt/Suite',
                    'required' => false,
                    'placeholder' => $this->getAddressPlaceholder('apartment')
                ])
            );
        }

        // City
        $this->addField(
            InputField::text('city', [
                'label' => $this->format === 'uk' ? 'Town/City' : 'City',
                'required' => true,
                'placeholder' => $this->getAddressPlaceholder('city'),
                'validationRules' => [
                    'alpha' => ['message' => 'City name should contain only letters and spaces']
                ]
            ])
        );

        // State/Province/County
        if ($this->format === 'us') {
            $this->addField(
                SelectField::make('state', $this->getUSStates(), [
                    'label' => 'State',
                    'required' => true,
                    'emptyOption' => 'Select State'
                ])
            );
        } elseif ($this->format === 'uk') {
            $this->addField(
                InputField::text('county', [
                    'label' => 'County',
                    'required' => false,
                    'placeholder' => 'e.g., Greater London'
                ])
            );
        } else {
            $this->addField(
                InputField::text('state', [
                    'label' => 'State/Province/Region',
                    'required' => false,
                    'placeholder' => $this->getAddressPlaceholder('state')
                ])
            );
        }

        // Postal/ZIP Code
        $postalConfig = [
            'label' => $this->format === 'us' ? 'ZIP Code' : 'Postal Code',
            'required' => true,
            'placeholder' => $this->getAddressPlaceholder('postal_code')
        ];

        if ($this->validatePostalCode) {
            $postalConfig['validationRules'] = [
                'pattern' => [
                    'value' => $this->getPostalCodePattern(),
                    'message' => 'Invalid ' . ($this->format === 'us' ? 'ZIP' : 'postal') . ' code format'
                ]
            ];
        }

        $this->addField(InputField::text('postal_code', $postalConfig));

        // Country
        if (!empty($this->countries)) {
            $this->addField(
                SelectField::make('country', $this->countries, [
                    'label' => 'Country',
                    'required' => true,
                    'value' => $this->getDefaultCountry()
                ])
            );
        } else {
            $this->addField(
                InputField::text('country', [
                    'label' => 'Country',
                    'required' => true,
                    'value' => $this->getDefaultCountry(),
                    'placeholder' => $this->getAddressPlaceholder('country')
                ])
            );
        }
    }

    /**
     * Get placeholder text for address field
     * 
     * @param string $fieldName Field name
     * @return string
     */
    private function getAddressPlaceholder(string $fieldName): string
    {
        $placeholders = match($this->format) {
            'us' => [
                'street' => '123 Main Street',
                'apartment' => 'Apt 4B',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States'
            ],
            'uk' => [
                'street' => '10 Downing Street',
                'apartment' => 'Flat 2',
                'city' => 'London',
                'county' => 'Greater London',
                'postal_code' => 'SW1A 2AA',
                'country' => 'United Kingdom'
            ],
            default => [
                'street' => '123 Main Street',
                'apartment' => 'Unit 4B',
                'city' => 'City Name',
                'state' => 'State/Province',
                'postal_code' => '12345',
                'country' => 'Country'
            ]
        };

        return $placeholders[$fieldName] ?? '';
    }

    /**
     * Get postal code validation pattern
     * 
     * @return string
     */
    private function getPostalCodePattern(): string
    {
        return match($this->format) {
            'us' => '^[0-9]{5}(?:-[0-9]{4})?$',  // 12345 or 12345-6789
            'uk' => '^[A-Z]{1,2}[0-9]{1,2}[A-Z]?\s?[0-9][A-Z]{2}$',  // SW1A 2AA
            'canada' => '^[A-Z][0-9][A-Z]\s?[0-9][A-Z][0-9]$',  // K1A 0B1
            default => '^[A-Za-z0-9\s\-]{3,10}$'  // Generic
        };
    }

    /**
     * Get default country based on format
     * 
     * @return string
     */
    private function getDefaultCountry(): string
    {
        return match($this->format) {
            'us' => 'US',
            'uk' => 'GB',
            'canada' => 'CA',
            default => ''
        };
    }

    /**
     * Get default country list
     * 
     * @return array
     */
    private function getDefaultCountries(): array
    {
        return [
            ['US', 'United States'],
            ['GB', 'United Kingdom'],
            ['CA', 'Canada'],
            ['AU', 'Australia'],
            ['DE', 'Germany'],
            ['FR', 'France'],
            ['IT', 'Italy'],
            ['ES', 'Spain'],
            ['JP', 'Japan'],
            ['CN', 'China'],
            ['IN', 'India'],
            ['BR', 'Brazil'],
            ['MX', 'Mexico'],
            ['NL', 'Netherlands'],
            ['SE', 'Sweden'],
            ['NO', 'Norway'],
            ['DK', 'Denmark'],
            ['FI', 'Finland'],
            ['IE', 'Ireland'],
            ['NZ', 'New Zealand']
        ];
    }

    /**
     * Get US states list
     * 
     * @return array
     */
    private function getUSStates(): array
    {
        return [
            ['AL', 'Alabama'], ['AK', 'Alaska'], ['AZ', 'Arizona'],
            ['AR', 'Arkansas'], ['CA', 'California'], ['CO', 'Colorado'],
            ['CT', 'Connecticut'], ['DE', 'Delaware'], ['FL', 'Florida'],
            ['GA', 'Georgia'], ['HI', 'Hawaii'], ['ID', 'Idaho'],
            ['IL', 'Illinois'], ['IN', 'Indiana'], ['IA', 'Iowa'],
            ['KS', 'Kansas'], ['KY', 'Kentucky'], ['LA', 'Louisiana'],
            ['ME', 'Maine'], ['MD', 'Maryland'], ['MA', 'Massachusetts'],
            ['MI', 'Michigan'], ['MN', 'Minnesota'], ['MS', 'Mississippi'],
            ['MO', 'Missouri'], ['MT', 'Montana'], ['NE', 'Nebraska'],
            ['NV', 'Nevada'], ['NH', 'New Hampshire'], ['NJ', 'New Jersey'],
            ['NM', 'New Mexico'], ['NY', 'New York'], ['NC', 'North Carolina'],
            ['ND', 'North Dakota'], ['OH', 'Ohio'], ['OK', 'Oklahoma'],
            ['OR', 'Oregon'], ['PA', 'Pennsylvania'], ['RI', 'Rhode Island'],
            ['SC', 'South Carolina'], ['SD', 'South Dakota'], ['TN', 'Tennessee'],
            ['TX', 'Texas'], ['UT', 'Utah'], ['VT', 'Vermont'],
            ['VA', 'Virginia'], ['WA', 'Washington'], ['WV', 'West Virginia'],
            ['WI', 'Wisconsin'], ['WY', 'Wyoming']
        ];
    }

    /**
     * Static factory for US address format
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function us(string $name, array $config = []): self
    {
        $config['format'] = 'us';
        return new self($name, $config);
    }

    /**
     * Static factory for UK address format
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function uk(string $name, array $config = []): self
    {
        $config['format'] = 'uk';
        return new self($name, $config);
    }

    /**
     * Static factory for Canadian address format
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function canada(string $name, array $config = []): self
    {
        $config['format'] = 'canada';
        return new self($name, $config);
    }

    /**
     * Static factory for international address format
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function international(string $name, array $config = []): self
    {
        $config['format'] = 'international';
        return new self($name, $config);
    }
}
