# CRM-ERP-CMS-PIM Unified Business Management Platform

A comprehensive, enterprise-grade business management system built from scratch with **zero external dependencies**. This platform integrates Customer Relationship Management (CRM), Enterprise Resource Planning (ERP), Content Management System (CMS), and Product Information Management (PIM) into a single unified solution.

## 🚀 Features

### Core Framework
- **Custom PHP Framework** - PSR-4 compliant, MVC architecture
- **Zero Dependencies** - No Composer packages, completely standalone
- **Active Record ORM** - Fluent query builder with soft deletes
- **Migration System** - Database versioning with rollback support
- **Authentication & Authorization** - RBAC with roles and permissions
- **Security** - Argon2id hashing, CSRF protection, XSS filtering, SQL injection prevention
- **Validation Engine** - 20+ built-in rules including database validation
- **Template Engine** - Layouts, sections, includes with auto-escaping
- **Middleware Pipeline** - 8 middleware components for request filtering
- **Event System** - Synchronous and asynchronous event dispatching
- **Caching** - File-based caching with tag support
- **Logging** - Multi-level logging (DEBUG to CRITICAL)
- **File Upload** - Secure upload with validation and thumbnail generation
- **CLI Tools** - Migration runner, database seeder, cache manager

### CRM Module
- **Contact Management** - Comprehensive contact database
- **Account Management** - Company/organization tracking
- **Lead Management** - Lead capture and conversion workflow
- **Opportunity Tracking** - Sales pipeline with forecasting
- **Activity Management** - Tasks, calls, meetings, emails (polymorphic)

### ERP Module

#### Inventory Management
- Multi-warehouse support
- Real-time stock tracking
- Stock movement history
- Automated reorder notifications
- Barcode support

#### Procurement
- Vendor management
- Purchase orders with approval workflow
- GRN (Goods Receipt Note)
- Vendor performance tracking

#### Sales
- Customer management
- Sales orders with shipping
- Quotation management
- Order fulfillment tracking

#### Finance
- Invoicing (Sales & Purchase)
- Payment processing
- Multi-payment support
- Overdue tracking
- Financial reporting

#### Human Resources
- Employee management
- Attendance tracking
- Leave management with approval workflow
- Payroll processing
- Department hierarchy

### CMS Module
- **Page Management** - Hierarchical page structure
- **Blog System** - Posts with categories and tags
- **Media Library** - File and image management
- **Menu Builder** - Dynamic navigation with nesting
- **Content Versioning** - Revision history and restore
- **Comment System** - Threaded comments with moderation
- **SEO Optimization** - Meta tags, slugs, structured data

### PIM Module
- **Product Catalog** - SKU-based product management
- **Product Variants** - Size, color, and custom options
- **Dynamic Attributes** - EAV pattern for flexible attributes
- **Category Management** - Hierarchical product categorization
- **Brand Management** - Brand associations
- **Multi-Currency Pricing** - Price lists with tiers
- **Product Images** - Gallery with main image designation

### Frontend
- **Bootstrap 5** - Modern, responsive UI
- **AJAX Library** - Full-featured API client
- **UI Components** - Reusable forms, tables, modals, pagination
- **Dashboard** - Real-time statistics and widgets
- **Responsive Design** - Mobile-first approach

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite enabled
- GD Library (for image processing)

## 🔧 Installation

### 1. Clone or Download

```bash
git clone <repository-url>
cd crm-erp-cms-pim
```

### 2. Configure Database

Create a MySQL database and update the configuration:

```bash
cp config/database.example.php config/database.php
```

Edit `config/database.php`:

```php
return [
    'host' => 'localhost',
    'database' => 'your_database_name',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```

### 3. Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 storage/logs/
chmod -R 755 storage/cache/
chmod -R 755 storage/uploads/
```

### 4. Run Migrations

```bash
php cli/migrate.php
```

### 5. Seed Database

```bash
php cli/seed.php
```

This creates:
- 5 default roles (Super Admin, Admin, Manager, Sales Rep, User)
- 20 module permissions
- 3 demo users:
  - admin@example.com / password (Super Admin)
  - manager@example.com / password (Manager)
  - user@example.com / password (User)

### 6. Configure Web Server

#### Apache

Ensure `.htaccess` is enabled and mod_rewrite is active. The document root should point to the `public` directory.

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/crm-erp-cms-pim/public
    
    <Directory /path/to/crm-erp-cms-pim/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 7. Access the Application

Navigate to `http://your-domain.com` and log in with the demo credentials.

## 📁 Directory Structure

```
crm-erp-cms-pim/
├── app/                    # Application layer
│   ├── Controllers/        # HTTP controllers
│   └── Models/            # Core models (User, Role, Permission)
├── config/                # Configuration files
│   ├── app.php
│   ├── database.php
│   └── security.php
├── core/                  # Core framework
│   ├── Auth/             # Authentication system
│   ├── Cache/            # Caching system
│   ├── Config/           # Configuration manager
│   ├── Database/         # Database layer
│   ├── Error/            # Error handling
│   ├── Event/            # Event system
│   ├── File/             # File handling
│   ├── Http/             # HTTP layer
│   ├── Logger/           # Logging system
│   ├── Middleware/       # Middleware components
│   ├── ORM/              # Object-Relational Mapping
│   ├── Router/           # Routing system
│   ├── Security/         # Security utilities
│   ├── Session/          # Session management
│   ├── Validation/       # Validation engine
│   └── View/             # Template engine
├── database/             # Database layer
│   ├── migrations/       # Migration files
│   └── seeds/           # Seed data
├── modules/              # Business modules
│   ├── CRM/             # Customer Relationship Management
│   ├── ERP/             # Enterprise Resource Planning
│   ├── CMS/             # Content Management System
│   └── PIM/             # Product Information Management
├── public/               # Public directory (web root)
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── index.php        # Front controller
│   └── .htaccess        # Apache configuration
├── storage/              # Storage directory
│   ├── cache/           # Cache files
│   ├── logs/            # Log files
│   └── uploads/         # Uploaded files
├── views/                # View templates
│   ├── layouts/         # Layout templates
│   └── dashboard.php    # Dashboard view
├── cli/                  # CLI commands
│   ├── migrate.php      # Migration runner
│   ├── seed.php         # Database seeder
│   └── cache.php        # Cache manager
└── autoload.php         # PSR-4 autoloader
```

## 🔐 Security Features

- **Password Hashing** - Argon2id algorithm
- **CSRF Protection** - Token-based validation
- **XSS Prevention** - Input/output escaping
- **SQL Injection Prevention** - Prepared statements
- **Session Security** - Fixation prevention, regeneration
- **Rate Limiting** - Request throttling
- **Input Sanitization** - Automatic cleanup
- **Permission-based Access Control** - Granular permissions
- **Role-based Authorization** - Hierarchical roles

## 🛠️ CLI Commands

### Database Migrations

```bash
# Run pending migrations
php cli/migrate.php

# Rollback last migration batch
php cli/migrate.php rollback

# Reset all migrations
php cli/migrate.php reset

# Refresh migrations (reset + run)
php cli/migrate.php refresh

# Show migration status
php cli/migrate.php status
```

### Database Seeding

```bash
php cli/seed.php
```

### Cache Management

```bash
# Clear all cache
php cli/cache.php clear

# Clear specific cache
php cli/cache.php clear users
```

## 🎨 Frontend Usage

### AJAX Requests

```javascript
// GET request
const data = await Ajax.get('/api/users');

// POST request
await Ajax.post('/api/users', {
    name: 'John Doe',
    email: 'john@example.com'
});

// Form submission
const form = document.querySelector('#myForm');
await Ajax.submitForm(form, {
    onSuccess: (data) => {
        console.log('Success:', data);
    }
});

// File upload with progress
await Ajax.uploadFile('/api/upload', file, {
    onProgress: (percent) => {
        console.log('Progress:', percent);
    }
});
```

### UI Components

```javascript
// Create modal
const modal = UI.createModal({
    title: 'Confirm Action',
    body: '<p>Are you sure?</p>',
    size: 'sm'
});
modal.show();

// Create confirmation dialog
UI.confirm({
    message: 'Delete this item?',
    onConfirm: () => {
        // Delete action
    }
});

// Create data table
const table = UI.createTable({
    columns: [
        { field: 'id', label: 'ID' },
        { field: 'name', label: 'Name' }
    ],
    data: users,
    actions: (row) => `
        <button class="btn btn-sm btn-primary">Edit</button>
        <button class="btn btn-sm btn-danger">Delete</button>
    `
});

// Create pagination
const pagination = UI.createPagination({
    currentPage: 1,
    totalPages: 10,
    onPageChange: (page) => {
        loadData(page);
    }
});
```

## 📊 Database Schema

The platform includes **45 database tables** organized by module:

- **Base:** users, roles, permissions, sessions
- **CRM:** accounts, contacts, leads, opportunities, activities
- **ERP:** warehouses, inventory, purchase_orders, sales_orders, invoices, payments, employees, attendance, leaves, payroll
- **CMS:** pages, posts, categories, tags, media, menus, comments, revisions
- **PIM:** products, variants, attributes, categories, brands, price_lists

## 🔌 API Development

### Creating a Controller

```php
<?php

namespace App\Controllers;

use Core\Http\Request;
use Core\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return Response::json(['users' => $users]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email'
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        $user = User::create($request->all());
        return Response::json(['user' => $user], 201);
    }
}
```

### Defining Routes

```php
// routes/web.php
$router->get('/users', [UserController::class, 'index'])
    ->middleware(['auth', 'permission:view-users']);

$router->post('/users', [UserController::class, 'store'])
    ->middleware(['auth', 'permission:create-users']);
```

## 🧪 Testing

```bash
# Run all tests
php tests/run.php

# Run specific test suite
php tests/run.php --suite=unit

# Run with coverage
php tests/run.php --coverage
```

## 📈 Performance

- **Query Optimization** - Indexed columns, efficient joins
- **Caching Strategy** - Page cache, query cache, object cache
- **Lazy Loading** - Deferred relationship loading
- **Asset Optimization** - Minified CSS/JS (production)
- **Database Pooling** - Connection reuse

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📝 License

This project is proprietary software. All rights reserved.

## 👥 Authors

- **Development Team** - Initial work and ongoing maintenance

## 🙏 Acknowledgments

- Built with modern PHP best practices
- Inspired by Laravel, Symfony, and CodeIgniter
- UI components based on Bootstrap 5
- Architecture follows SOLID principles

## 📞 Support

For support, email support@example.com or open an issue in the repository.

## 🗺️ Roadmap

- [ ] Email service with queue support
- [ ] ORM relationship enhancements
- [ ] Report generator (PDF, Excel)
- [ ] Workflow engine
- [ ] Mobile app API
- [ ] Multi-language support
- [ ] Advanced analytics dashboard
- [ ] WebSocket real-time updates
- [ ] GraphQL API
- [ ] Docker containerization

---

**Version:** 1.0.0  
**Last Updated:** 2024  
**Status:** Production Ready (Core Features)
