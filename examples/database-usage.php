<?php
// In your controller or service
use Core\Database\Database;
use Core\Di\Injectable;

class UserService
{
    use Injectable;

    protected $db;

    public function __construct()
    {
        $this->db = $this->container->get(Database::class);
    }

    public function getActiveUsers()
    {
        return $this->db->table('users')
            ->select(['id', 'name', 'email'])
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function createUser($userData)
    {
        return $this->db->table('users')
            ->insert($userData);
    }

    public function updateUser($userId, $userData)
    {
        return $this->db->table('users')
            ->where('id', $userId)
            ->update($userData);
    }

    public function getUserWithPosts($userId)
    {
        $user = $this->db->table('users')
            ->where('id', $userId)
            ->first();

        if ($user) {
            $user->posts = $this->db->table('posts')
                ->where('user_id', $userId)
                ->get();
        }

        return $user;
    }
}


// config/database.php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'persistent' => true, // for connection pooling
];