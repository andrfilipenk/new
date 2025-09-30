<?php
// app/_Core/Session/DatabaseSessionHandler.php
namespace Core\Session;

use Core\Database\Database;
use Core\Di\Injectable;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    use Injectable;

    protected Database $db;
    protected string $table = 'sessions';
    protected int $lifetime;
    protected bool $exists = false;

    public function __construct($config = [])
    {
        $this->lifetime = $config['lifetime'] ?? 3600;
        $this->table = $config['table'] ?? 'sessions';
        $this->db = $this->getDI()->get('db');
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return true; // Connection is managed by Database class
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $sessionId): string
    {
        $result = $this->db
            ->table($this->table)
            ->select(['data'])
            ->where('id', $sessionId)
            ->where('expires_at', '>', time())
            ->first();
        if ($result) {
            $this->exists = true;
            return $result['data'] ?? '';
        }
        $this->exists = false;
        return '';
    }

    public function write(string $sessionId, string $sessionData): bool
    {
        $expiresAt = time() + $this->lifetime;
        $data = [
            'id'            => $sessionId,
            'data'          => $sessionData,
            'expires_at'    => $expiresAt,
            'last_activity' => time(),
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];
        if ($this->exists) {
            return $this->db->table($this->table)
                ->where('id', $sessionId)
                ->update($data) > 0;
        }
        try {
            $this->db->table($this->table)->insert($data);
            $this->exists = true;
            return true;
        } catch (\Exception $e) {
            // Handle duplicate session ID (race condition)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->db->table($this->table)
                    ->where('id', $sessionId)
                    ->update($data) > 0;
            }
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $sessionId): bool
    {
        $this->exists = false;
        return $this->db->table($this->table)
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    public function gc(int $maxlifetime): int
    {
        return $this->db->table($this->table)
            ->where('expires_at', '<', time())
            ->delete();
    }

    public function setLifetime(int $lifetime): self
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * Get active sessions count
     */
    public function getActiveSessionsCount(): int
    {
        return $this->db->table($this->table)
            ->where('expires_at', '>', time())
            ->count();
    }

    /**
     * Get session by ID
     */
    public function getSession(string $sessionId): ?array
    {
        return $this->db->table($this->table)
            ->where('id', $sessionId)
            ->first();
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        return $this->gc(0);
    }
}