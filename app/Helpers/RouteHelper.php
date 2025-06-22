<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper
{
    /**
     * Register API routes with automatic configuration
     *
     * @param string $domain The domain name for the routes
     * @param array $routes Array of route definitions
     * @param array $options Additional options
     * @return void
     */
    public static function registerApiRoutes(string $domain, array $routes, array $options = []): void
    {
        $config = config('routes.api.config', []);
        $prefix = $config['prefixes'][$domain] ?? $domain;
        $middlewares = $config['middlewares'][$domain] ?? ['auth:sanctum'];

        Route::prefix($prefix)
            ->middleware($middlewares)
            ->group(function () use ($routes, $options) {
                foreach ($routes as $route) {
                    $method = $route['method'] ?? 'get';
                    $path = $route['path'] ?? '/';
                    $controller = $route['controller'];
                    $action = $route['action'];
                    $name = $route['name'] ?? null;

                    $routeInstance = Route::$method($path, [$controller, $action]);

                    if ($name) {
                        $routeInstance->name($name);
                    }

                    // Aplicar opciones adicionales
                    if (isset($route['middleware'])) {
                        $routeInstance->middleware($route['middleware']);
                    }

                    if (isset($route['where'])) {
                        $routeInstance->where($route['where']);
                    }
                }
            });
    }

    /**
     * Register API resource routes with automatic configuration
     *
     * @param string $domain The domain name for the routes
     * @param string $controller The controller class name
     * @param array $options Additional options for the resource
     * @return void
     */
    public static function registerApiResource(string $domain, string $controller, array $options = []): void
    {
        $config = config('routes.api.config', []);
        $prefix = $config['prefixes'][$domain] ?? $domain;
        $middlewares = $config['middlewares'][$domain] ?? ['auth:sanctum'];

        Route::prefix($prefix)
            ->middleware($middlewares)
            ->group(function () use ($controller, $options) {
                Route::apiResource('/', $controller, $options);
            });
    }

    /**
     * Generate API URL with version
     *
     * @param string $path The API path
     * @param string $version The API version
     * @return string The complete API URL
     */
    public static function apiUrl(string $path, string $version = 'v1'): string
    {
        return "/api/{$version}/{$path}";
    }

    /**
     * Generate route name with domain prefix
     *
     * @param string $domain The domain name
     * @param string $action The action name
     * @return string The complete route name
     */
    public static function routeName(string $domain, string $action): string
    {
        return "{$domain}.{$action}";
    }

    /**
     * Check if current request is an API route
     *
     * @return bool True if current request is API route
     */
    public static function isApiRoute(): bool
    {
        return request()->is('api/*');
    }

    /**
     * Get API version from current request URL
     *
     * @return string|null The API version or null if not found
     */
    public static function getApiVersion(): ?string
    {
        $path = request()->path();
        if (preg_match('/^api\/(v\d+)\//', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
