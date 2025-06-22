<?php

namespace App\Traits;

use App\Helpers\RouteHelper;

trait HasRouteHelpers
{
    /**
     * Get API URL for current resource
     *
     * @param string $path Optional path to append
     * @return string The complete API URL
     */
    protected function getApiUrl(string $path = ''): string
    {
        $resource = $this->getResourceName();
        $fullPath = $resource . ($path ? "/{$path}" : '');

        return RouteHelper::apiUrl($fullPath);
    }

    /**
     * Get route name for current resource
     *
     * @param string $action The action name
     * @return string The complete route name
     */
    protected function getRouteName(string $action): string
    {
        $resource = $this->getResourceName();
        return RouteHelper::routeName($resource, $action);
    }

    /**
     * Check if current request is API route
     *
     * @return bool True if current request is API route
     */
    protected function isApiRoute(): bool
    {
        return RouteHelper::isApiRoute();
    }

    /**
     * Get current API version
     *
     * @return string|null The API version or null if not specified
     */
    protected function getApiVersion(): ?string
    {
        return RouteHelper::getApiVersion();
    }

    /**
     * Generate pagination links with API URLs
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator The paginator instance
     * @return array Array of pagination links
     */
    protected function generateApiPaginationLinks($paginator): array
    {
        $links = [];

        if ($paginator->previousPageUrl()) {
            $links['prev'] = $this->convertToApiUrl($paginator->previousPageUrl());
        }

        if ($paginator->nextPageUrl()) {
            $links['next'] = $this->convertToApiUrl($paginator->nextPageUrl());
        }

        $links['first'] = $this->convertToApiUrl($paginator->url(1));
        $links['last'] = $this->convertToApiUrl($paginator->url($paginator->lastPage()));

        return $links;
    }

    /**
     * Convert web URL to API URL
     *
     * @param string $url The web URL to convert
     * @return string The API URL
     */
    protected function convertToApiUrl(string $url): string
    {
        // Convertir URL web a API
        $url = str_replace('/api/', '/api/v1/', $url);
        return $url;
    }

    /**
     * Get resource name from controller
     *
     * @return string The resource name
     */
    protected function getResourceName(): string
    {
        $className = class_basename($this);
        return strtolower(str_replace('Controller', '', $className));
    }

    /**
     * Generate API response with metadata
     *
     * @param mixed $data The response data
     * @param int $status HTTP status code
     * @param array $meta Additional metadata
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    protected function apiResponse($data, int $status = 200, array $meta = []): \Illuminate\Http\JsonResponse
    {
        $response = [
            'data' => $data,
            'meta' => array_merge($meta, [
                'api_version' => $this->getApiVersion(),
                'timestamp' => now()->toISOString(),
                'resource' => $this->getResourceName()
            ])
        ];

        return response()->json($response, $status);
    }
}
