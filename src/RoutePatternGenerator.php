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

    public function generate(Route $route): string
    {
        $path = preg_replace_callback('/\/{(\w+)\??}/', function ($matches) use ($route) {
            $paramName = $matches[1];
            $isOptional = str_contains($matches[0], '?');
            $pattern = $route->patterns[$paramName] ?? $this->aliases[$paramName] ?? $this->aliases['default'];
            return $isOptional ? "(?:/(?P<$paramName>$pattern))?" : "/(?P<$paramName>$pattern)";
        }, $route->groupPath . $route->path);

        return '#^' . $path . '/?$#';
    }

    public function setAlias(string $alias, string $pattern): static
    {
        $this->aliases[$alias] = $pattern;
        return $this;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }
}