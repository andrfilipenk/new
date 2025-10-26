<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */

use Core\Router;

// Home route
Router::get('/', function() {
    return view('welcome');
});

// Authentication Routes
Router::group(['middleware' => ['guest']], function() {
    // Login routes
    Router::get('/login', 'App\Controllers\AuthController@showLogin');
    Router::post('/login', 'App\Controllers\AuthController@login');
    
    // Register routes
    Router::get('/register', 'App\Controllers\AuthController@showRegister');
    Router::post('/register', 'App\Controllers\AuthController@register');
    
    // Password reset routes
    Router::get('/password/forgot', 'App\Controllers\AuthController@showForgotPassword');
    Router::post('/password/reset', 'App\Controllers\AuthController@resetPassword');
});

// Logout route (requires authentication)
Router::post('/logout', 'App\Controllers\AuthController@logout', ['middleware' => ['auth']]);

// Dashboard route (requires authentication)
Router::get('/dashboard', 'App\Controllers\DashboardController@index', ['middleware' => ['auth']]);

// API routes
Router::group(['prefix' => 'api/v1'], function() {
    
    // Public API routes
    Router::get('/health', function() {
        return [
            'status' => 'ok',
            'timestamp' => now(),
            'version' => '1.0.0',
        ];
    });
    
    // Protected API routes (will add auth middleware later)
    Router::group(['middleware' => ['auth']], function() {
        // User routes
        Router::get('/user', function() {
            return ['message' => 'User endpoint'];
        });
    });
});

// Workflow Designer Routes
Router::get('/workflow-designer', 'App\Controllers\WorkflowDesignerController@index');

// Workflow Designer API Routes
Router::group(['prefix' => 'api/workflow-designer'], function() {
    Router::get('/step-types', 'App\Controllers\WorkflowDesignerController@getStepTypes');
    Router::get('/list', 'App\Controllers\WorkflowDesignerController@listWorkflows');
    Router::get('/get/{id}', 'App\Controllers\WorkflowDesignerController@getWorkflow');
    Router::post('/save', 'App\Controllers\WorkflowDesignerController@saveWorkflow');
    Router::post('/validate', 'App\Controllers\WorkflowDesignerController@validateWorkflow');
    Router::delete('/delete/{id}', 'App\Controllers\WorkflowDesignerController@deleteWorkflow');
});

// ===========================
// CRM Module Routes
// ===========================

// CRM Account Routes
Router::group(['prefix' => 'crm/accounts', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CRM\Controllers\AccountController@index');
    Router::get('/create', 'Modules\CRM\Controllers\AccountController@create');
    Router::post('/', 'Modules\CRM\Controllers\AccountController@store');
    Router::get('/{id}', 'Modules\CRM\Controllers\AccountController@show');
    Router::get('/{id}/edit', 'Modules\CRM\Controllers\AccountController@edit');
    Router::put('/{id}', 'Modules\CRM\Controllers\AccountController@update');
    Router::delete('/{id}', 'Modules\CRM\Controllers\AccountController@destroy');
});

// CRM Contact Routes
Router::group(['prefix' => 'crm/contacts', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CRM\Controllers\ContactController@index');
    Router::get('/create', 'Modules\CRM\Controllers\ContactController@create');
    Router::post('/', 'Modules\CRM\Controllers\ContactController@store');
    Router::get('/{id}', 'Modules\CRM\Controllers\ContactController@show');
    Router::get('/{id}/edit', 'Modules\CRM\Controllers\ContactController@edit');
    Router::put('/{id}', 'Modules\CRM\Controllers\ContactController@update');
    Router::delete('/{id}', 'Modules\CRM\Controllers\ContactController@destroy');
});

// CRM Opportunity Routes
Router::group(['prefix' => 'crm/opportunities', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CRM\Controllers\OpportunityController@index');
    Router::get('/pipeline', 'Modules\CRM\Controllers\OpportunityController@pipeline');
    Router::get('/create', 'Modules\CRM\Controllers\OpportunityController@create');
    Router::post('/', 'Modules\CRM\Controllers\OpportunityController@store');
    Router::get('/{id}', 'Modules\CRM\Controllers\OpportunityController@show');
    Router::get('/{id}/edit', 'Modules\CRM\Controllers\OpportunityController@edit');
    Router::put('/{id}', 'Modules\CRM\Controllers\OpportunityController@update');
    Router::delete('/{id}', 'Modules\CRM\Controllers\OpportunityController@destroy');
});

// CRM Activity Routes
Router::group(['prefix' => 'crm/activities', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CRM\Controllers\ActivityController@index');
    Router::get('/create', 'Modules\CRM\Controllers\ActivityController@create');
    Router::post('/', 'Modules\CRM\Controllers\ActivityController@store');
    Router::get('/{id}', 'Modules\CRM\Controllers\ActivityController@show');
    Router::get('/{id}/edit', 'Modules\CRM\Controllers\ActivityController@edit');
    Router::put('/{id}', 'Modules\CRM\Controllers\ActivityController@update');
    Router::delete('/{id}', 'Modules\CRM\Controllers\ActivityController@destroy');
});

// CRM Lead Routes (existing)
Router::group(['prefix' => 'crm/leads', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CRM\Controllers\LeadController@index');
    Router::get('/create', 'Modules\CRM\Controllers\LeadController@create');
    Router::post('/', 'Modules\CRM\Controllers\LeadController@store');
    Router::get('/{id}', 'Modules\CRM\Controllers\LeadController@show');
    Router::get('/{id}/edit', 'Modules\CRM\Controllers\LeadController@edit');
    Router::put('/{id}', 'Modules\CRM\Controllers\LeadController@update');
    Router::delete('/{id}', 'Modules\CRM\Controllers\LeadController@destroy');
    Router::post('/{id}/convert', 'Modules\CRM\Controllers\LeadController@convert');
});

// ===========================
// CRM API Routes
// ===========================

Router::group(['prefix' => 'api/crm', 'middleware' => ['auth']], function() {
    
    // Account API
    Router::get('/accounts', 'Modules\CRM\Controllers\AccountController@index');
    Router::post('/accounts', 'Modules\CRM\Controllers\AccountController@store');
    Router::get('/accounts/{id}', 'Modules\CRM\Controllers\AccountController@show');
    Router::put('/accounts/{id}', 'Modules\CRM\Controllers\AccountController@update');
    Router::delete('/accounts/{id}', 'Modules\CRM\Controllers\AccountController@destroy');
    Router::post('/accounts/{id}/assign', 'Modules\CRM\Controllers\AccountController@assign');
    Router::get('/accounts/statistics', 'Modules\CRM\Controllers\AccountController@statistics');
    
    // Contact API
    Router::get('/contacts', 'Modules\CRM\Controllers\ContactController@index');
    Router::post('/contacts', 'Modules\CRM\Controllers\ContactController@store');
    Router::get('/contacts/{id}', 'Modules\CRM\Controllers\ContactController@show');
    Router::put('/contacts/{id}', 'Modules\CRM\Controllers\ContactController@update');
    Router::delete('/contacts/{id}', 'Modules\CRM\Controllers\ContactController@destroy');
    Router::post('/contacts/{id}/assign', 'Modules\CRM\Controllers\ContactController@assign');
    Router::post('/contacts/{id}/link-account', 'Modules\CRM\Controllers\ContactController@linkToAccount');
    
    // Opportunity API
    Router::get('/opportunities', 'Modules\CRM\Controllers\OpportunityController@index');
    Router::get('/opportunities/pipeline', 'Modules\CRM\Controllers\OpportunityController@pipeline');
    Router::post('/opportunities', 'Modules\CRM\Controllers\OpportunityController@store');
    Router::get('/opportunities/{id}', 'Modules\CRM\Controllers\OpportunityController@show');
    Router::put('/opportunities/{id}', 'Modules\CRM\Controllers\OpportunityController@update');
    Router::delete('/opportunities/{id}', 'Modules\CRM\Controllers\OpportunityController@destroy');
    Router::post('/opportunities/{id}/stage', 'Modules\CRM\Controllers\OpportunityController@updateStage');
    Router::post('/opportunities/{id}/won', 'Modules\CRM\Controllers\OpportunityController@markAsWon');
    Router::post('/opportunities/{id}/lost', 'Modules\CRM\Controllers\OpportunityController@markAsLost');
    Router::get('/opportunities/statistics', 'Modules\CRM\Controllers\OpportunityController@statistics');
    
    // Activity API
    Router::get('/activities', 'Modules\CRM\Controllers\ActivityController@index');
    Router::post('/activities', 'Modules\CRM\Controllers\ActivityController@store');
    Router::get('/activities/{id}', 'Modules\CRM\Controllers\ActivityController@show');
    Router::put('/activities/{id}', 'Modules\CRM\Controllers\ActivityController@update');
    Router::delete('/activities/{id}', 'Modules\CRM\Controllers\ActivityController@destroy');
    Router::post('/activities/{id}/complete', 'Modules\CRM\Controllers\ActivityController@markCompleted');
    Router::get('/activities/upcoming', 'Modules\CRM\Controllers\ActivityController@upcoming');
    Router::get('/activities/overdue', 'Modules\CRM\Controllers\ActivityController@overdue');
    
    // Lead API (existing)
    Router::get('/leads', 'Modules\CRM\Controllers\LeadController@index');
    Router::post('/leads', 'Modules\CRM\Controllers\LeadController@store');
    Router::get('/leads/{id}', 'Modules\CRM\Controllers\LeadController@show');
    Router::put('/leads/{id}', 'Modules\CRM\Controllers\LeadController@update');
    Router::delete('/leads/{id}', 'Modules\CRM\Controllers\LeadController@destroy');
    Router::post('/leads/{id}/convert', 'Modules\CRM\Controllers\LeadController@convert');
    Router::post('/leads/{id}/qualify', 'Modules\CRM\Controllers\LeadController@qualify');
    Router::post('/leads/{id}/assign', 'Modules\CRM\Controllers\LeadController@assign');
});

// ===========================
// ERP Module Routes
// ===========================

// ERP Inventory Routes
Router::group(['prefix' => 'erp/inventory', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\InventoryController@index');
    Router::get('/{id}', 'Modules\ERP\Controllers\InventoryController@show');
    Router::post('/adjust', 'Modules\ERP\Controllers\InventoryController@adjust');
    Router::post('/transfer', 'Modules\ERP\Controllers\InventoryController@transfer');
});

// ERP Sales Order Routes (sidebar link: /erp/sales)
Router::group(['prefix' => 'erp/sales', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\SalesOrderController@index');
    Router::get('/create', 'Modules\ERP\Controllers\SalesOrderController@create');
    Router::post('/', 'Modules\ERP\Controllers\SalesOrderController@store');
    Router::get('/{id}', 'Modules\ERP\Controllers\SalesOrderController@show');
    Router::put('/{id}', 'Modules\ERP\Controllers\SalesOrderController@update');
});

// ERP Purchase Order Routes (sidebar link: /erp/purchases)
Router::group(['prefix' => 'erp/purchases', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\PurchaseOrderController@index');
    Router::get('/create', 'Modules\ERP\Controllers\PurchaseOrderController@create');
    Router::post('/', 'Modules\ERP\Controllers\PurchaseOrderController@store');
    Router::get('/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@show');
    Router::put('/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@update');
});

// Also keep original purchase-orders route for backwards compatibility
Router::group(['prefix' => 'erp/purchase-orders', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\PurchaseOrderController@index');
    Router::get('/create', 'Modules\ERP\Controllers\PurchaseOrderController@create');
    Router::post('/', 'Modules\ERP\Controllers\PurchaseOrderController@store');
    Router::get('/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@show');
    Router::put('/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@update');
});

// ERP Finance Routes (sidebar link: /erp/finance)
Router::group(['prefix' => 'erp/finance', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\FinanceController@index');
    Router::get('/dashboard', 'Modules\ERP\Controllers\FinanceController@dashboard');
});

// ERP Invoice Routes
Router::group(['prefix' => 'erp/invoices', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\InvoiceController@index');
    Router::get('/create', 'Modules\ERP\Controllers\InvoiceController@create');
    Router::post('/', 'Modules\ERP\Controllers\InvoiceController@store');
    Router::get('/{id}', 'Modules\ERP\Controllers\InvoiceController@show');
});

// ERP Employee Routes
Router::group(['prefix' => 'erp/employees', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\EmployeeController@index');
    Router::get('/create', 'Modules\ERP\Controllers\EmployeeController@create');
    Router::post('/', 'Modules\ERP\Controllers\EmployeeController@store');
    Router::get('/{id}', 'Modules\ERP\Controllers\EmployeeController@show');
    Router::get('/{id}/edit', 'Modules\ERP\Controllers\EmployeeController@edit');
    Router::put('/{id}', 'Modules\ERP\Controllers\EmployeeController@update');
});

// ERP Payroll Routes
Router::group(['prefix' => 'erp/payroll', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\ERP\Controllers\PayrollController@index');
    Router::get('/{id}', 'Modules\ERP\Controllers\PayrollController@show');
    Router::post('/generate', 'Modules\ERP\Controllers\PayrollController@generate');
});

// ===========================
// ERP API Routes
// ===========================

Router::group(['prefix' => 'api/erp', 'middleware' => ['auth']], function() {
    
    // Inventory API
    Router::get('/inventory', 'Modules\ERP\Controllers\InventoryController@index');
    Router::get('/inventory/{id}', 'Modules\ERP\Controllers\InventoryController@show');
    Router::post('/inventory/adjust', 'Modules\ERP\Controllers\InventoryController@adjust');
    Router::post('/inventory/transfer', 'Modules\ERP\Controllers\InventoryController@transfer');
    Router::get('/inventory/low-stock', 'Modules\ERP\Controllers\InventoryController@lowStock');
    
    // Purchase Order API
    Router::get('/purchase-orders', 'Modules\ERP\Controllers\PurchaseOrderController@index');
    Router::post('/purchase-orders', 'Modules\ERP\Controllers\PurchaseOrderController@store');
    Router::get('/purchase-orders/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@show');
    Router::put('/purchase-orders/{id}', 'Modules\ERP\Controllers\PurchaseOrderController@update');
    Router::post('/purchase-orders/{id}/approve', 'Modules\ERP\Controllers\PurchaseOrderController@approve');
    Router::post('/purchase-orders/{id}/reject', 'Modules\ERP\Controllers\PurchaseOrderController@reject');
    Router::post('/purchase-orders/{id}/receive', 'Modules\ERP\Controllers\PurchaseOrderController@receive');
    
    // Invoice API
    Router::get('/invoices', 'Modules\ERP\Controllers\InvoiceController@index');
    Router::post('/invoices', 'Modules\ERP\Controllers\InvoiceController@store');
    Router::get('/invoices/{id}', 'Modules\ERP\Controllers\InvoiceController@show');
    Router::post('/invoices/{id}/payment', 'Modules\ERP\Controllers\InvoiceController@recordPayment');
    Router::post('/invoices/{id}/send', 'Modules\ERP\Controllers\InvoiceController@send');
    Router::post('/invoices/{id}/void', 'Modules\ERP\Controllers\InvoiceController@void');
    
    // Employee API
    Router::get('/employees', 'Modules\ERP\Controllers\EmployeeController@index');
    Router::post('/employees', 'Modules\ERP\Controllers\EmployeeController@store');
    Router::get('/employees/{id}', 'Modules\ERP\Controllers\EmployeeController@show');
    Router::put('/employees/{id}', 'Modules\ERP\Controllers\EmployeeController@update');
    Router::post('/employees/attendance', 'Modules\ERP\Controllers\EmployeeController@recordAttendance');
    
    // Payroll API
    Router::get('/payroll', 'Modules\ERP\Controllers\PayrollController@index');
    Router::get('/payroll/{id}', 'Modules\ERP\Controllers\PayrollController@show');
    Router::post('/payroll/generate', 'Modules\ERP\Controllers\PayrollController@generate');
    Router::post('/payroll/{id}/approve', 'Modules\ERP\Controllers\PayrollController@approve');
    Router::post('/payroll/{id}/process-payment', 'Modules\ERP\Controllers\PayrollController@processPayment');
    Router::get('/payroll/summary', 'Modules\ERP\Controllers\PayrollController@summary');
});

// ===========================
// CMS Module Routes
// ===========================

// CMS Post Routes
Router::group(['prefix' => 'cms/posts', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CMS\Controllers\PostController@index');
    Router::get('/create', 'Modules\CMS\Controllers\PostController@create');
    Router::post('/', 'Modules\CMS\Controllers\PostController@store');
    Router::get('/{id}', 'Modules\CMS\Controllers\PostController@show');
    Router::get('/{id}/edit', 'Modules\CMS\Controllers\PostController@edit');
    Router::put('/{id}', 'Modules\CMS\Controllers\PostController@update');
    Router::delete('/{id}', 'Modules\CMS\Controllers\PostController@destroy');
});

// CMS Media Routes
Router::group(['prefix' => 'cms/media', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CMS\Controllers\MediaController@index');
    Router::post('/upload', 'Modules\CMS\Controllers\MediaController@upload');
    Router::delete('/{id}', 'Modules\CMS\Controllers\MediaController@destroy');
});

// CMS Menu Routes
Router::group(['prefix' => 'cms/menus', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CMS\Controllers\MenuController@index');
    Router::get('/{id}', 'Modules\CMS\Controllers\MenuController@show');
    Router::post('/', 'Modules\CMS\Controllers\MenuController@store');
    Router::put('/{id}/items', 'Modules\CMS\Controllers\MenuController@updateItems');
});

// CMS Page Routes (existing)
Router::group(['prefix' => 'cms/pages', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\CMS\Controllers\PageController@index');
    Router::get('/create', 'Modules\CMS\Controllers\PageController@create');
    Router::post('/', 'Modules\CMS\Controllers\PageController@store');
    Router::get('/{id}', 'Modules\CMS\Controllers\PageController@show');
    Router::get('/{id}/edit', 'Modules\CMS\Controllers\PageController@edit');
    Router::put('/{id}', 'Modules\CMS\Controllers\PageController@update');
    Router::delete('/{id}', 'Modules\CMS\Controllers\PageController@destroy');
});

// ===========================
// PIM Module Routes
// ===========================

// PIM Product Routes (existing)
Router::group(['prefix' => 'pim/products', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\PIM\Controllers\ProductController@index');
    Router::get('/create', 'Modules\PIM\Controllers\ProductController@create');
    Router::post('/', 'Modules\PIM\Controllers\ProductController@store');
    Router::get('/{id}', 'Modules\PIM\Controllers\ProductController@show');
    Router::get('/{id}/edit', 'Modules\PIM\Controllers\ProductController@edit');
    Router::put('/{id}', 'Modules\PIM\Controllers\ProductController@update');
    Router::delete('/{id}', 'Modules\PIM\Controllers\ProductController@destroy');
});

// PIM Category Routes
Router::group(['prefix' => 'pim/categories', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\PIM\Controllers\CategoryController@index');
    Router::post('/', 'Modules\PIM\Controllers\CategoryController@store');
    Router::put('/{id}', 'Modules\PIM\Controllers\CategoryController@update');
    Router::delete('/{id}', 'Modules\PIM\Controllers\CategoryController@destroy');
});

// PIM Attribute Routes
Router::group(['prefix' => 'pim/attributes', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\PIM\Controllers\AttributeController@index');
    Router::post('/', 'Modules\PIM\Controllers\AttributeController@store');
    Router::put('/{id}', 'Modules\PIM\Controllers\AttributeController@update');
    Router::delete('/{id}', 'Modules\PIM\Controllers\AttributeController@destroy');
});

// PIM Price List Routes
Router::group(['prefix' => 'pim/price-lists', 'middleware' => ['auth']], function() {
    Router::get('/', 'Modules\PIM\Controllers\PriceListController@index');
    Router::get('/{id}', 'Modules\PIM\Controllers\PriceListController@show');
    Router::post('/', 'Modules\PIM\Controllers\PriceListController@store');
    Router::post('/{id}/items', 'Modules\PIM\Controllers\PriceListController@addItem');
});

// ===========================
// CMS API Routes
// ===========================

Router::group(['prefix' => 'api/cms', 'middleware' => ['auth']], function() {
    
    // Post API
    Router::get('/posts', 'Modules\CMS\Controllers\PostController@index');
    Router::post('/posts', 'Modules\CMS\Controllers\PostController@store');
    Router::get('/posts/{id}', 'Modules\CMS\Controllers\PostController@show');
    Router::put('/posts/{id}', 'Modules\CMS\Controllers\PostController@update');
    Router::delete('/posts/{id}', 'Modules\CMS\Controllers\PostController@destroy');
    Router::post('/posts/{id}/publish', 'Modules\CMS\Controllers\PostController@publish');
    
    // Media API
    Router::get('/media', 'Modules\CMS\Controllers\MediaController@index');
    Router::post('/media/upload', 'Modules\CMS\Controllers\MediaController@upload');
    Router::delete('/media/{id}', 'Modules\CMS\Controllers\MediaController@destroy');
    
    // Menu API
    Router::get('/menus', 'Modules\CMS\Controllers\MenuController@index');
    Router::get('/menus/{id}', 'Modules\CMS\Controllers\MenuController@show');
    Router::post('/menus', 'Modules\CMS\Controllers\MenuController@store');
    Router::put('/menus/{id}/items', 'Modules\CMS\Controllers\MenuController@updateItems');
    
    // Page API (existing)
    Router::get('/pages', 'Modules\CMS\Controllers\PageController@index');
    Router::post('/pages', 'Modules\CMS\Controllers\PageController@store');
    Router::get('/pages/{id}', 'Modules\CMS\Controllers\PageController@show');
    Router::put('/pages/{id}', 'Modules\CMS\Controllers\PageController@update');
    Router::delete('/pages/{id}', 'Modules\CMS\Controllers\PageController@destroy');
});

// ===========================
// PIM API Routes
// ===========================

Router::group(['prefix' => 'api/pim', 'middleware' => ['auth']], function() {
    
    // Product API (existing)
    Router::get('/products', 'Modules\PIM\Controllers\ProductController@index');
    Router::post('/products', 'Modules\PIM\Controllers\ProductController@store');
    Router::get('/products/{id}', 'Modules\PIM\Controllers\ProductController@show');
    Router::put('/products/{id}', 'Modules\PIM\Controllers\ProductController@update');
    Router::delete('/products/{id}', 'Modules\PIM\Controllers\ProductController@destroy');
    
    // Category API
    Router::get('/categories', 'Modules\PIM\Controllers\CategoryController@index');
    Router::get('/categories/tree', 'Modules\PIM\Controllers\CategoryController@tree');
    Router::post('/categories', 'Modules\PIM\Controllers\CategoryController@store');
    Router::put('/categories/{id}', 'Modules\PIM\Controllers\CategoryController@update');
    Router::delete('/categories/{id}', 'Modules\PIM\Controllers\CategoryController@destroy');
    
    // Attribute API
    Router::get('/attributes', 'Modules\PIM\Controllers\AttributeController@index');
    Router::post('/attributes', 'Modules\PIM\Controllers\AttributeController@store');
    Router::put('/attributes/{id}', 'Modules\PIM\Controllers\AttributeController@update');
    Router::delete('/attributes/{id}', 'Modules\PIM\Controllers\AttributeController@destroy');
    
    // Price List API
    Router::get('/price-lists', 'Modules\PIM\Controllers\PriceListController@index');
    Router::get('/price-lists/{id}', 'Modules\PIM\Controllers\PriceListController@show');
    Router::post('/price-lists', 'Modules\PIM\Controllers\PriceListController@store');
    Router::post('/price-lists/{id}/items', 'Modules\PIM\Controllers\PriceListController@addItem');
});

// ===========================
// Workflow Management Routes
// ===========================

// Workflow Management UI Routes
Router::group(['prefix' => 'workflows', 'middleware' => ['auth']], function() {
    Router::get('/', 'App\Controllers\WorkflowController@index');
    Router::get('/create', 'App\Controllers\WorkflowController@create');
    Router::get('/{id}', 'App\Controllers\WorkflowController@show');
    Router::get('/{id}/edit', 'App\Controllers\WorkflowController@edit');
    Router::get('/{id}/designer', 'App\Controllers\WorkflowDesignerController@show');
    
    // Workflow Instances
    Router::get('/instances/{id}', 'App\Controllers\WorkflowController@showInstance');
    Router::get('/approvals/pending', 'App\Controllers\WorkflowController@pendingApprovals');
});

// Workflow API Routes
Router::group(['prefix' => 'api/workflows', 'middleware' => ['auth']], function() {
    // Workflow CRUD
    Router::get('/', 'App\Controllers\WorkflowController@apiList');
    Router::post('/', 'App\Controllers\WorkflowController@store');
    Router::get('/{id}', 'App\Controllers\WorkflowController@apiShow');
    Router::put('/{id}', 'App\Controllers\WorkflowController@update');
    Router::delete('/{id}', 'App\Controllers\WorkflowController@destroy');
    
    // Workflow Steps
    Router::post('/{id}/steps', 'App\Controllers\WorkflowController@addStep');
    Router::put('/steps/{stepId}', 'App\Controllers\WorkflowController@updateStep');
    Router::delete('/steps/{stepId}', 'App\Controllers\WorkflowController@removeStep');
    
    // Workflow Instances & Actions
    Router::post('/{id}/start', 'App\Controllers\WorkflowController@startWorkflow');
    Router::post('/{instanceId}/approve', 'App\Controllers\WorkflowController@approve');
    Router::post('/{instanceId}/reject', 'App\Controllers\WorkflowController@reject');
    Router::post('/{instanceId}/cancel', 'App\Controllers\WorkflowController@cancel');
    
    // Workflow Queries
    Router::get('/pending', 'App\Controllers\WorkflowController@apiPendingApprovals');
    Router::get('/instances/{id}/details', 'App\Controllers\WorkflowController@getInstanceDetails');
    Router::get('/instances/{id}/report', 'App\Controllers\WorkflowController@generateReport');
});

// ===========================
// Admin Module Routes
// ===========================

// Admin User Management Routes
Router::group(['prefix' => 'admin/users', 'middleware' => ['auth', 'role:admin']], function() {
    Router::get('/', 'App\Controllers\Admin\UserController@index');
    Router::get('/create', 'App\Controllers\Admin\UserController@create');
    Router::post('/', 'App\Controllers\Admin\UserController@store');
    Router::get('/{id}', 'App\Controllers\Admin\UserController@show');
    Router::get('/{id}/edit', 'App\Controllers\Admin\UserController@edit');
    Router::put('/{id}', 'App\Controllers\Admin\UserController@update');
    Router::delete('/{id}', 'App\Controllers\Admin\UserController@destroy');
});

// Admin Role Management Routes
Router::group(['prefix' => 'admin/roles', 'middleware' => ['auth', 'role:admin']], function() {
    Router::get('/', 'App\Controllers\Admin\RoleController@index');
    Router::get('/create', 'App\Controllers\Admin\RoleController@create');
    Router::post('/', 'App\Controllers\Admin\RoleController@store');
    Router::get('/{id}', 'App\Controllers\Admin\RoleController@show');
    Router::get('/{id}/edit', 'App\Controllers\Admin\RoleController@edit');
    Router::put('/{id}', 'App\Controllers\Admin\RoleController@update');
    Router::delete('/{id}', 'App\Controllers\Admin\RoleController@destroy');
});

// Admin Settings Routes
Router::group(['prefix' => 'admin/settings', 'middleware' => ['auth', 'role:admin']], function() {
    Router::get('/', 'App\Controllers\Admin\SettingsController@index');
    Router::post('/', 'App\Controllers\Admin\SettingsController@update');
});

// Example resource routes (uncomment when controllers are ready)
// Router::resource('users', App\Controllers\UserController::class);