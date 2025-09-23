<?php
// examples/Adr/AdrServiceProvider.php
namespace Examples\Adr;

use Core\Di\Interface\Container as ContainerInterface;
use Examples\Adr\User\Repository\UserRepository;
use Examples\Adr\User\Domain\CreateUserDomain;
use Examples\Adr\User\Action\CreateUserAction;
use Examples\Adr\User\Responder\UserResponder;

/**
 * ADR Service Provider - Registers ADR components in DI container
 */
class AdrServiceProvider
{
    public function register(ContainerInterface $di): void
    {
        // Register Repository
        $di->set('userRepository', function() {
            return new UserRepository();
        });

        // Register Domain Services
        $di->set('createUserDomain', function($di) {
            return new CreateUserDomain(
                $di->get('userRepository')
            );
        });

        // Register Actions
        $di->set('CreateUserAction', function($di) {
            return new CreateUserAction(
                $di->get('createUserDomain')
            );
        });

        // Register Responders
        $di->set('UserResponder', function() {
            return new UserResponder();
        });

        // Register ADR Controllers
        $di->set('Examples\\Adr\\User\\Controller\\UserAdrController', function($di) {
            $controller = new \Examples\Adr\User\Controller\UserAdrController();
            $controller->setDI($di);
            return $controller;
        });
    }
}