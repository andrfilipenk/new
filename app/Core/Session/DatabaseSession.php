<?php
// app/Core/Session/DatabaseSession.php
namespace Core\Session;

use Core\Di\Injectable;
use Core\Events\EventAware;

class DatabaseSession implements SessionInterface
{
    use Injectable, EventAware;

    protected DatabaseSessionHandler $handler;
    protected bool $started = false;
    protected string $sessionId;
    protected array $data = [];

    public function __construct(DatabaseSessionHandler $handler)
    {
        $this->handler = $handler;
        if (session_status() === PHP_SESSION_NONE) {
            $this->setSaveHandler();
            $this->start();
        } else {
            $this->started = true;
            $this->sessionId = session_id();
            $this->data = $_SESSION ?? [];
        }
    }

    protected function setSaveHandler(): void
    {
        session_set_save_handler(
            [$this->handler, 'open'],
            [$this->handler, 'close'],
            [$this->handler, 'read'],
            [$this->handler, 'write'],
            [$this->handler, 'destroy'],
            [$this->handler, 'gc']
        );
        register_shutdown_function('session_write_close');
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        $this->fireEvent('session:beforeStart', $this);
        $config = $this->getDI()->get('config')['session'] ?? [];
        $cookieParams = [
            'lifetime' => $config['cookie_lifetime'] ?? 0,
            'path' => $config['cookie_path'] ?? '/',
            'domain' => $config['cookie_domain'] ?? '',
            'secure' => $config['cookie_secure'] ?? false,
            'httponly' => $config['cookie_httponly'] ?? true,
            'samesite' => $config['cookie_samesite'] ?? 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        if (session_start()) {
            $this->started = true;
            $this->sessionId = session_id();
            $this->data = $_SESSION;
            $this->fireEvent('session:afterStart', $this);
            return true;
        }
        return false;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function getId(): string
    {
        return $this->sessionId;
    }

    public function setId(string $id): void
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot change session ID after session has started');
        }
        session_id($id);
        $this->sessionId = $id;
    }

    public function getName(): string
    {
        return session_name();
    }

    public function setName(string $name): void
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot change session name after session has started');
        }
        session_name($name);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key], $_SESSION[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
        $_SESSION = [];
    }

    public function destroy(): void
    {
        if ($this->started) {
            $this->fireEvent('session:beforeDestroy', $this);
            $this->data = [];
            $_SESSION = [];
            session_destroy();
            $this->started = false;
            $this->fireEvent('session:afterDestroy', $this);
        }
    }

    public function regenerate(bool $deleteOldSession = true): bool
    {
        if (!$this->started) {
            return false;
        }
        $this->fireEvent('session:beforeRegenerate', $this);
        if ($deleteOldSession) {
            session_regenerate_id(true);
        } else {
            session_regenerate_id(false);
        }
        $this->sessionId = session_id();
        $this->fireEvent('session:afterRegenerate', $this);
        return true;
    }

    /**
     * Get the underlying handler instance
     */
    public function getHandler(): DatabaseSessionHandler
    {
        return $this->handler;
    }
}