<?php
// app/Core/Session/Session.php
namespace Core\Session;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Session implements SessionInterface
{
    use Injectable, EventAware;

    protected $started  = false;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        } else {
            $this->started = true;
        }
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        $this->fireEvent('session:beforeStart', $this);
        $this->started = session_start();
        if ($this->isStarted()) {
            $this->fireEvent('session:afterStart', $this);
        }
        return $this->started;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function getId(): string
    {
        return session_id();
    }

    public function setId(string $id)
    {
        session_id($id);
    }

    public function getName(): string
    {
        return session_name();
    }

    public function setName(string $name)
    {
        session_name($name);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function clear()
    {
        session_unset();
    }

    public function destroy()
    {
        if ($this->started) {
            $this->fireEvent('session:beforeDestroy', $this);
            session_destroy();
            $this->started = false;
            $this->fireEvent('session:afterDestroy', $this);
        }
    }
}