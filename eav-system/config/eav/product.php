<?php
// config/eav/product.php
// Example product entity type configuration

return [
    'label' => 'Product',
    'entity_table' => 'eav_entity_product',
    'storage_strategy' => 'eav',
    'attributes' => [
        [
            'code' => 'name',
            'label' => 'Product Name',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_searchable' => true,
            'is_filterable' => false,
            'sort_order' => 10
        ],
        [
            'code' => 'sku',
            'label' => 'SKU',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_unique' => true,
            'is_searchable' => true,
            'sort_order' => 20
        ],
        [
            'code' => 'description',
            'label' => 'Description',
            'backend_type' => 'text',
            'frontend_type' => 'textarea',
            'is_required' => false,
            'is_searchable' => true,
            'sort_order' => 30
        ],
        [
            'code' => 'price',
            'label' => 'Price',
            'backend_type' => 'decimal',
            'frontend_type' => 'number',
            'is_required' => true,
            'is_filterable' => true,
            'default_value' => 0.00,
            'sort_order' => 40
        ],
        [
            'code' => 'quantity',
            'label' => 'Quantity',
            'backend_type' => 'int',
            'frontend_type' => 'number',
            'is_required' => true,
            'default_value' => 0,
            'sort_order' => 50
        ],
        [
            'code' => 'is_active',
            'label' => 'Is Active',
            'backend_type' => 'int',
            'frontend_type' => 'boolean',
            'is_required' => false,
            'default_value' => 1,
            'sort_order' => 60
        ],
        [
            'code' => 'created_date',
            'label' => 'Created Date',
            'backend_type' => 'datetime',
            'frontend_type' => 'date',
            'is_required' => false,
            'sort_order' => 70
        ]
    ]
];
