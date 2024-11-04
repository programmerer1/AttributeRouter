<?php
declare(strict_types=1);

namespace AttributeRouter;

class RoutePatternGenerator
{
    private array $aliases = [
        'default' => '[a-zA-Z0-9-_]+',
        'id' => '[0-9]+',
        'number' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
    ];

    public function generate(Route $route, string $prefix = ''): string
    {
        $path = preg_replace_callback('/\/{(\w+)\??}/', function ($matches) use ($route) {
            $paramName = $matches[1];
            $isOptional = str_contains($matches[0], '?');
            $pattern = $route->patterns[$paramName] ?? $this->aliases[$paramName] ?? $this->aliases['default'];
            return $isOptional ? "(?:/(?P<$paramName>$pattern))?" : "/(?P<$paramName>$pattern)";
        }, $prefix . $route->path);

        return '#^' . $path . '$#';
    }
}