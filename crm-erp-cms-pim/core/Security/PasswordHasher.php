<?php

namespace Core\Security;

use Core\Config\Config;

/**
 * Password Hasher
 * 
 * Provides secure password hashing using Argon2id algorithm
 */
class PasswordHasher
{
    /**
     * Hash a password
     * 
     * @param string $password
     * @return string
     */
    public static function hash(string $password): string
    {
        $algorithm = Config::get('security.password.algorithm', PASSWORD_ARGON2ID);
        $options = [
            'memory_cost' => Config::get('security.password.memory_cost', 2048),
            'time_cost' => Config::get('security.password.time_cost', 4),
            'threads' => Config::get('security.password.threads', 2),
        ];

        $hash = password_hash($password, $algorithm, $options);

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed');
        }

        return $hash;
    }

    /**
     * Verify a password against a hash
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs rehashing
     * 
     * @param string $hash
     * @return bool
     */
    public static function needsRehash(string $hash): bool
    {
        $algorithm = Config::get('security.password.algorithm', PASSWORD_ARGON2ID);
        $options = [
            'memory_cost' => Config::get('security.password.memory_cost', 2048),
            'time_cost' => Config::get('security.password.time_cost', 4),
            'threads' => Config::get('security.password.threads', 2),
        ];

        return password_needs_rehash($hash, $algorithm, $options);
    }

    /**
     * Generate a random password
     * 
     * @param int $length
     * @return string
     */
    public static function generate(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $password;
    }
}
