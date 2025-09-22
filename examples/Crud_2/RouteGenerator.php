<?php
// app/Core/Crud/RouteGenerator.php
namespace Core\Crud;

class RouteGenerator
{
    public static function crud(string $path, string $controller): array
    {
        $basePath = trim($path, '/');
        
        return [
            "GET|POST|PUT|DELETE|PATCH /{$basePath}" => [
                'controller' => $controller,
                'action' => 'index',
                'method' => 'GET'
            ],
            "GET /{$basePath}/create" => [
                'controller' => $controller,
                'action' => 'create',
                'method' => 'GET'
            ],
            "POST /{$basePath}" => [
                'controller' => $controller,
                'action' => 'store',
                'method' => 'POST'
            ],
            "GET /{$basePath}/{id}" => [
                'controller' => $controller,
                'action' => 'show',
                'method' => 'GET'
            ],
            "GET /{$basePath}/{id}/edit" => [
                'controller' => $controller,
                'action' => 'edit',
                'method' => 'GET'
            ],
            "PUT|PATCH /{$basePath}/{id}" => [
                'controller' => $controller,
                'action' => 'update'
            ],
            "DELETE /{$basePath}/{id}" => [
                'controller' => $controller,
                'action' => 'delete'
            ]
        ];
    }
}