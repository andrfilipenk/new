<?php
// app/Core/Session/Session.php
namespace Core\Session;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Session implements SessionInterface
{
    use Injectable, EventAware;

    protected $started  = false;
    protected $flashKey = '_flash';

    public function __construct()
    {
        // Auto-start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        } else {
            $this->started = true;
        }
        // Initialize flash messages
        $this->initFlash();
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        // Trigger beforeStart event
        $this->fireEvent('session:beforeStart', $this);
        $this->started = session_start();
        if ($this->started) {
            // Trigger afterStart event
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

    public function flash(string $key, $value)
    {
        $this->set($this->flashKey . '.new.' . $key, $value);
    }

    public function getFlash(string $key, $default = null)
    {
        $value = $this->get($this->flashKey . '.old.' . $key, $default);
        $this->removeFlash($key);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return $this->has($this->flashKey . '.old.' . $key);
    }

    public function removeFlash(string $key)
    {
        $this->remove($this->flashKey . '.old.' . $key);
        $this->remove($this->flashKey . '.new.' . $key);
    }

    public function clearFlash()
    {
        $this->remove($this->flashKey . '.old');
        $this->remove($this->flashKey . '.new');
    }

    public function destroy()
    {
        if ($this->started) {
            // Trigger beforeDestroy event
            $this->fireEvent('session:beforeDestroy', $this);
            session_destroy();
            $this->started = false;
            // Trigger afterDestroy event
            $this->fireEvent('session:afterDestroy', $this);
        }
    }

    public function __destruct()
    {
        // Move new flash messages to old for next request
        $this->ageFlash();
    }

    protected function initFlash()
    {
        if (!isset($_SESSION[$this->flashKey])) {
            $_SESSION[$this->flashKey] = [
                'old' => [],
                'new' => []
            ];
        }
    }

    protected function ageFlash()
    {
        if (isset($_SESSION[$this->flashKey]['old'])) {
            unset($_SESSION[$this->flashKey]['old']);
        }
        if (isset($_SESSION[$this->flashKey]['new'])) {
            $_SESSION[$this->flashKey]['old'] = $_SESSION[$this->flashKey]['new'];
            unset($_SESSION[$this->flashKey]['new']);
        }
    }
}