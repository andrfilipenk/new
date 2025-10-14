<?php
// app/Core/Cookie/Cookie.php
namespace Core\Cookie;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Cookie implements CookieInterface
{
    use Injectable, EventAware;

    protected $defaults = [
        'expires'   => 0,
        'path'      => '/',
        'domain'    => '',
        'secure'    => false,
        'httponly'  => true,
        'samesite'  => 'Lax' // None, Lax, or Strict
    ];

    public function __construct(array $defaults = [])
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }

    public function set(string $name, string $value, array $options = [], ?int $expires = null): bool
    {
        $options = array_merge($this->defaults, $options);
        if ($expires !== null) {
            $options['expires'] = $expires > 0 ? time() + $expires * 86400 : $expires;
        }
        $event = $this->fireEvent('cookie:beforeSet', [$this, ['name' => $name, 'value' => $value, 'options' => $options]]);
        if ($event->isPropagationStopped()) return false;
        $result = setcookie($name, $value, $options);
        if ($result) {
            $_COOKIE[$name] = $value;
            $this->fireEvent('cookie:afterSet', [$this, ['name' => $name, 'value' => $value, 'options' => $options]]);
        }
        return $result;
    }

    public function get(string $name, $default = null)
    {
        // Trigger beforeGet event
        $event = $this->fireEvent('cookie:beforeGet',  [$this,
            [
                'name' => $name
            ]
        ]);
        if ($event->isPropagationStopped()) {
            return $default;
        }
        $value = $_COOKIE[$name] ?? $default;
        // Trigger afterGet event
        $this->fireEvent('cookie:afterGet',  [$this, 
            [
                'name'  => $name,
                'value' => $value
            ]
        ]);
        return $value;
    }

    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public function delete(string $name, array $options = []): bool
    {
        // Merge with defaults
        $options = array_merge($this->defaults, $options);
        // Set expiration to past to delete
        $options['expires'] = time() - 3600;
        // Trigger beforeDelete event
        $event = $this->fireEvent('cookie:beforeDelete', [ $this,
            [
                'name'      => $name,
                'options'   => $options
            ]
        ]);
        if ($event->isPropagationStopped()) {
            return false;
        }
        // Delete the cookie
        $result = setcookie(
            $name,
            '',
            [
                'expires'   => $options['expires'],
                'path'      => $options['path'],
                'domain'    => $options['domain'],
                'secure'    => $options['secure'],
                'httponly'  => $options['httponly'],
                'samesite'  => $options['samesite']
            ]
        );
        if ($result) {
            // Also remove from $_COOKIE for immediate effect
            unset($_COOKIE[$name]);
            // Trigger afterDelete event
            $this->fireEvent('cookie:afterDelete', [$this, 
                [
                    'name' => $name
                ]
            ]);
        }
        return $result;
    }

    public function setDefaults(array $defaults): void
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Set a cookie that expires in a specific number of days
     */
    public function setForDays(string $name, string $value, int $days, array $options = []): bool
    {
        $options['expires'] = $days;
        return $this->set($name, $value, $options);
    }

    /**
     * Set a cookie that expires in a specific number of hours
     */
    public function setForHours(string $name, string $value, int $hours, array $options = []): bool
    {
        $options['expires'] = time() + ($hours * 3600);
        return $this->set($name, $value, $options);
    }

    /**
     * Set a persistent cookie (expires in 1 year)
     */
    public function setPersistent(string $name, string $value, array $options = []): bool
    {
        $options['expires'] = 365; // 1 year in days
        return $this->set($name, $value, $options);
    }

    /**
     * Set a session cookie (expires when browser closes)
     */
    public function setSession(string $name, string $value, array $options = []): bool
    {
        $options['expires'] = 0;
        return $this->set($name, $value, $options);
    }

    /**
     * Get all cookies
     */
    public function getAll(): array
    {
        return $_COOKIE;
    }

    /**
     * Clear all cookies
     */
    public function clearAll(array $options = []): bool
    {
        $success = true;
        foreach (array_keys($_COOKIE) as $name) {
            if (!$this->delete($name, $options)) {
                $success = false;
            }
        }
        return $success;
    }
}