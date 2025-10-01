<?php
// seeders/UserSeeder.php
use Core\Database\Seeder;
use Admin\Model\User;
use Admin\Model\Groups;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin users
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@company.com',
                'password' => 'admin123',
                'custom_id' => 1001,
                'role' => 'admin'
            ],
            [
                'name' => 'System Manager',
                'email' => 'manager@company.com', 
                'password' => 'manager123',
                'custom_id' => 1002,
                'role' => 'leader'
            ]
        ];
        
        // Create department users
        $departmentUsers = [
            [
                'name' => 'Production Manager',
                'email' => 'production@company.com',
                'password' => 'prod123',
                'custom_id' => 2001,
                'department' => 'Production',
                'role' => 'leader'
            ],
            [
                'name' => 'Quality Inspector',
                'email' => 'quality@company.com',
                'password' => 'quality123',
                'custom_id' => 2002,
                'department' => 'Quality Assurance',
                'role' => 'worker'
            ],
            [
                'name' => 'Warehouse Staff',
                'email' => 'warehouse@company.com',
                'password' => 'warehouse123',
                'custom_id' => 2003,
                'department' => 'Warehouse',
                'role' => 'worker'
            ],
            [
                'name' => 'Maintenance Tech',
                'email' => 'maintenance@company.com',
                'password' => 'maint123',
                'custom_id' => 2004,
                'department' => 'Maintenance',
                'role' => 'worker'
            ]
        ];
        
        // Create HR users
        $hrUsers = [
            [
                'name' => 'HR Manager',
                'email' => 'hr@company.com',
                'password' => 'hr123',
                'custom_id' => 3001,
                'department' => 'Human Resources',
                'role' => 'leader'
            ],
            [
                'name' => 'HR Assistant',
                'email' => 'hr.assistant@company.com',
                'password' => 'hrassist123',
                'custom_id' => 3002,
                'department' => 'Human Resources',
                'role' => 'worker'
            ]
        ];
        
        // Create all users
        $allUsers = array_merge($adminUsers, $departmentUsers, $hrUsers);
        
        foreach ($allUsers as $userData) {
            $user = new User();
            $user->name = $userData['name'];
            $user->email = $userData['email'];
            $user->password = $userData['password']; // This will be hashed automatically
            $user->custom_id = $userData['custom_id'];
            $user->save();
            
            // Assign role
            if (isset($userData['role'])) {
                $user->assignRole($userData['role']);
            }
            
            echo "Created user: {$userData['name']}\n";
        }
    }
}