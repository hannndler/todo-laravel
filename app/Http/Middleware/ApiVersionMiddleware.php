<?php

namespace App\Http\Middleware;

use App\Helpers\RouteHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    /**
     * Handle API versioning for incoming requests
     *
     * @param Request $request The HTTP request
     * @param Closure $next The next middleware in the stack
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si es una ruta API
        if (!RouteHelper::isApiRoute()) {
            return $next($request);
        }

        // Obtener la versión de la API
        $version = RouteHelper::getApiVersion();

        // Si no hay versión especificada, usar v1 por defecto
        if (!$version) {
            $version = 'v1';
        }

        // Verificar si la versión es soportada
        $supportedVersions = ['v1', 'v2'];
        if (!in_array($version, $supportedVersions)) {
            return response()->json([
                'error' => 'API version not supported',
                'supported_versions' => $supportedVersions,
                'current_version' => $version
            ], 400);
        }

        // Agregar la versión al request para que esté disponible en los controladores
        $request->attributes->set('api_version', $version);

        // Agregar headers de respuesta
        $response = $next($request);
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-API-Supported-Versions', implode(', ', $supportedVersions));

        return $response;
    }
}
