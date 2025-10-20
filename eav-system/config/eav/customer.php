<?php
// config/eav/customer.php
// Example customer entity type configuration

return [
    'label' => 'Customer',
    'entity_table' => 'eav_entity_customer',
    'storage_strategy' => 'eav',
    'attributes' => [
        [
            'code' => 'first_name',
            'label' => 'First Name',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_searchable' => true,
            'sort_order' => 10
        ],
        [
            'code' => 'last_name',
            'label' => 'Last Name',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_searchable' => true,
            'sort_order' => 20
        ],
        [
            'code' => 'email',
            'label' => 'Email Address',
            'backend_type' => 'varchar',
            'frontend_type' => 'email',
            'is_required' => true,
            'is_unique' => true,
            'is_searchable' => true,
            'validation_rules' => ['email'],
            'sort_order' => 30
        ],
        [
            'code' => 'phone',
            'label' => 'Phone Number',
            'backend_type' => 'varchar',
            'frontend_type' => 'tel',
            'is_required' => false,
            'is_searchable' => true,
            'sort_order' => 40
        ],
        [
            'code' => 'date_of_birth',
            'label' => 'Date of Birth',
            'backend_type' => 'datetime',
            'frontend_type' => 'date',
            'is_required' => false,
            'sort_order' => 50
        ],
        [
            'code' => 'address',
            'label' => 'Address',
            'backend_type' => 'text',
            'frontend_type' => 'textarea',
            'is_required' => false,
            'sort_order' => 60
        ],
        [
            'code' => 'is_verified',
            'label' => 'Is Verified',
            'backend_type' => 'int',
            'frontend_type' => 'boolean',
            'is_required' => false,
            'default_value' => 0,
            'sort_order' => 70
        ]
    ]
];
