<?php
// app/Module/Provider/SessionServiceProvider.php
namespace Module\Provider;

use Core\Session\Session;
use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container;

class SessionServiceProvider implements ServiceProvider
{
    public function register(Container $di): void
    {
        $di->set('session', function() use ($di) {
            $config = $di->get('config');
            $sessionConfig = $config['session'] ?? [];
            $session = new Session();
            // Configure session settings if provided
            if (!empty($sessionConfig)) {
                #$this->configureSession($session, $sessionConfig);
            }
            // Inject DI container
            $session->setDI($di);
            // Inject events manager if available
            if ($di->has('eventsManager')) {
                $session->setEventsManager($di->get('eventsManager'));
            }
            return $session;
        });
    }
    
    protected function configureSession(Session $session, array $config): void
    {
        if (isset($config['name'])) {
            $session->setName($config['name']);
        }
        if (isset($config['lifetime'])) {
            ini_set('session.gc_maxlifetime', $config['lifetime']);
        }
        if (isset($config['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $config['cookie_lifetime']);
        }
        if (isset($config['cookie_path'])) {
            ini_set('session.cookie_path', $config['cookie_path']);
        }
        if (isset($config['cookie_domain'])) {
            ini_set('session.cookie_domain', $config['cookie_domain']);
        }
        if (isset($config['cookie_secure'])) {
            ini_set('session.cookie_secure', $config['cookie_secure']);
        }
        if (isset($config['cookie_httponly'])) {
            ini_set('session.cookie_httponly', $config['cookie_httponly']);
        }
    }
}