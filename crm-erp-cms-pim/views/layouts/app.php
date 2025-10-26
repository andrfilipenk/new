<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'CRM-ERP-CMS-PIM Platform'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 60px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
        }
        
        /* Header */
        .app-header {
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .app-header .navbar-brand {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        /* Sidebar */
        .app-sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: #2d3748;
            color: #cbd5e0;
            overflow-y: auto;
            z-index: 1020;
            transition: transform 0.3s;
        }
        
        .app-sidebar .nav-link {
            color: #cbd5e0;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.75rem;
            transition: all 0.2s;
        }
        
        .app-sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .app-sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .app-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .app-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 2rem;
            min-height: calc(100vh - var(--header-height));
            background: #f7fafc;
        }
        
        /* Cards */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .app-sidebar {
                transform: translateX(-100%);
            }
            
            .app-sidebar.show {
                transform: translateX(0);
            }
            
            .app-content {
                margin-left: 0;
            }
        }
        
        /* User Dropdown */
        .user-dropdown {
            cursor: pointer;
        }
        
        .user-dropdown img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 8px;
        }
    </style>
    
    <?php Core\View\ViewEngine::yield('styles'); ?>
</head>
<body>
    <!-- Header -->
    <nav class="app-header navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none me-2" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <a class="navbar-brand" href="<?php echo url('/dashboard'); ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i> Business Platform
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-link text-white position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><a class="dropdown-item" href="#"><small>New lead assigned to you</small></a></li>
                        <li><a class="dropdown-item" href="#"><small>Meeting in 30 minutes</small></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">View all</a></li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <div class="user-dropdown d-flex align-items-center" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(auth()->user()->name ?? 'User'); ?>&background=667eea&color=fff" alt="User">
                        <span class="d-none d-md-inline"><?php echo e(auth()->user()->name ?? 'User'); ?></span>
                        <i class="bi bi-chevron-down ms-2"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo url('/profile'); ?>"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo url('/settings'); ?>"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logout-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <div class="app-sidebar" id="sidebar">
        <nav class="nav flex-column py-3">
            <a class="nav-link active" href="<?php echo url('/dashboard'); ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            
            <div class="px-3 mt-3 mb-2 text-uppercase small fw-bold text-muted">CRM</div>
            <a class="nav-link" href="<?php echo url('/crm/leads'); ?>">
                <i class="bi bi-person-plus"></i> Leads
            </a>
            <a class="nav-link" href="<?php echo url('/crm/contacts'); ?>">
                <i class="bi bi-people"></i> Contacts
            </a>
            <a class="nav-link" href="<?php echo url('/crm/accounts'); ?>">
                <i class="bi bi-building"></i> Accounts
            </a>
            <a class="nav-link" href="<?php echo url('/crm/opportunities'); ?>">
                <i class="bi bi-graph-up"></i> Opportunities
            </a>
            <a class="nav-link" href="<?php echo url('/crm/activities'); ?>">
                <i class="bi bi-calendar-check"></i> Activities
            </a>
            
            <div class="px-3 mt-3 mb-2 text-uppercase small fw-bold text-muted">ERP</div>
            <a class="nav-link" href="<?php echo url('/erp/inventory'); ?>">
                <i class="bi bi-box-seam"></i> Inventory
            </a>
            <a class="nav-link" href="<?php echo url('/erp/sales'); ?>">
                <i class="bi bi-cart"></i> Sales
            </a>
            <a class="nav-link" href="<?php echo url('/erp/purchases'); ?>">
                <i class="bi bi-cart-plus"></i> Purchases
            </a>
            <a class="nav-link" href="<?php echo url('/erp/finance'); ?>">
                <i class="bi bi-currency-dollar"></i> Finance
            </a>
            
            <div class="px-3 mt-3 mb-2 text-uppercase small fw-bold text-muted">CMS</div>
            <a class="nav-link" href="<?php echo url('/cms/pages'); ?>">
                <i class="bi bi-file-text"></i> Pages
            </a>
            <a class="nav-link" href="<?php echo url('/cms/posts'); ?>">
                <i class="bi bi-journal-text"></i> Posts
            </a>
            <a class="nav-link" href="<?php echo url('/cms/media'); ?>">
                <i class="bi bi-image"></i> Media
            </a>
            
            <div class="px-3 mt-3 mb-2 text-uppercase small fw-bold text-muted">PIM</div>
            <a class="nav-link" href="<?php echo url('/pim/products'); ?>">
                <i class="bi bi-box"></i> Products
            </a>
            <a class="nav-link" href="<?php echo url('/pim/categories'); ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            
            <div class="px-3 mt-3 mb-2 text-uppercase small fw-bold text-muted">System</div>
            <a class="nav-link" href="<?php echo url('/admin/users'); ?>">
                <i class="bi bi-people-fill"></i> Users
            </a>
            <a class="nav-link" href="<?php echo url('/admin/roles'); ?>">
                <i class="bi bi-shield-check"></i> Roles
            </a>
            <a class="nav-link" href="<?php echo url('/admin/settings'); ?>">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <main class="app-content">
        <?php Core\View\ViewEngine::yield('content'); ?>
    </main>
    
    <!-- Hidden Logout Form -->
    <form id="logout-form" action="<?php echo url('/logout'); ?>" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo e(\Core\Session\SessionManager::get('csrf_token') ?? ''); ?>">
    </form>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inject application base path for JavaScript
        window.APP_BASE_PATH = '<?php 
            try {
                $request = \Core\Request::capture();
                echo $request->basePath();
            } catch (\Exception $e) {
                echo '';
            }
        ?>';
        
        // URL helper function for JavaScript
        function appUrl(path) {
            if (!path) return window.APP_BASE_PATH || '/';
            path = path.startsWith('/') ? path : '/' + path;
            return (window.APP_BASE_PATH || '') + path;
        }
        
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Active link highlight
        const currentPath = window.location.pathname;
        document.querySelectorAll('.app-sidebar .nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Logout form submission handler
        document.getElementById('logout-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('logout-form').submit();
        });
    </script>
    
    <?php Core\View\ViewEngine::yield('scripts'); ?>
</body>
</html>
