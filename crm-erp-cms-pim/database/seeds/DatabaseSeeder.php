<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Core\Database\DB;

/**
 * Database Seeder
 * 
 * Seeds initial data for the application
 */
class DatabaseSeeder
{
    public function run(): void
    {
        echo "Seeding database...\n";
        
        $this->seedRoles();
        $this->seedPermissions();
        $this->seedUsers();
        $this->assignRolesAndPermissions();
        
        echo "Database seeding completed!\n";
    }
    
    protected function seedRoles(): void
    {
        echo "- Seeding roles...\n";
        
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access', 'level' => 100],
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Administrative access', 'level' => 90],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Team management access', 'level' => 70],
            ['name' => 'Sales Representative', 'slug' => 'sales_rep', 'description' => 'Sales module access', 'level' => 50],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Basic user access', 'level' => 10],
        ];
        
        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }
    
    protected function seedPermissions(): void
    {
        echo "- Seeding permissions...\n";
        
        $modules = ['crm', 'erp', 'cms', 'pim'];
        $actions = ['view', 'create', 'edit', 'delete'];
        
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => ucfirst($action) . ' ' . strtoupper($module),
                    'slug' => $module . '.' . $action,
                    'module' => $module,
                    'description' => ucfirst($action) . ' access for ' . strtoupper($module) . ' module',
                ]);
            }
        }
        
        // Additional permissions
        $additional = [
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'system', 'description' => 'Manage system users'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'system', 'description' => 'Manage roles and permissions'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'system', 'description' => 'Access system reports'],
            ['name' => 'System Settings', 'slug' => 'settings.manage', 'module' => 'system', 'description' => 'Modify system settings'],
        ];
        
        foreach ($additional as $perm) {
            Permission::create($perm);
        }
    }
    
    protected function seedUsers(): void
    {
        echo "- Seeding users...\n";
        
        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_active' => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Demo Manager
        User::create([
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'is_active' => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Demo Sales Rep
        User::create([
            'name' => 'Jane Sales',
            'email' => 'sales@example.com',
            'password' => 'password123',
            'is_active' => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    protected function assignRolesAndPermissions(): void
    {
        echo "- Assigning roles and permissions...\n";
        
        // Get roles
        $superAdminRole = DB::table('roles')->where('slug', '=', 'super_admin')->first();
        $managerRole = DB::table('roles')->where('slug', '=', 'manager')->first();
        $salesRole = DB::table('roles')->where('slug', '=', 'sales_rep')->first();
        
        // Get users
        $admin = DB::table('users')->where('email', '=', 'admin@example.com')->first();
        $manager = DB::table('users')->where('email', '=', 'manager@example.com')->first();
        $sales = DB::table('users')->where('email', '=', 'sales@example.com')->first();
        
        // Assign roles to users
        $roleUser = [
            ['user_id' => $admin['id'], 'role_id' => $superAdminRole['id'], 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $manager['id'], 'role_id' => $managerRole['id'], 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $sales['id'], 'role_id' => $salesRole['id'], 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach ($roleUser as $ru) {
            DB::table('role_user')->insert($ru);
        }
        
        // Assign all permissions to super admin
        $allPermissions = DB::table('permissions')->get();
        foreach ($allPermissions as $permission) {
            DB::table('permission_role')->insert([
                'role_id' => $superAdminRole['id'],
                'permission_id' => $permission['id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign CRM permissions to manager and sales roles
        $crmPermissions = DB::table('permissions')->where('module', '=', 'crm')->get();
        foreach ($crmPermissions as $permission) {
            $permussionRole = [
                ['role_id' => $managerRole['id'], 'permission_id' => $permission['id'], 'created_at' => now(), 'updated_at' => now()],
                ['role_id' => $salesRole['id'], 'permission_id' => $permission['id'], 'created_at' => now(), 'updated_at' => now()],
            ];
            foreach($permussionRole as $pr) {
                DB::table('permission_role')->insert($pr);
            }
        }
    }
}
