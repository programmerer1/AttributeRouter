<?php

declare(strict_types=1);

namespace AttributeRouter;

use AttributeRouter\Exception\MethodNotAllowedException;
use AttributeRouter\Service\LocaleService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use AttributeRouter\Exception\NotFoundHttpException;

class Router
{
    private array $routes = [];
    private array $current;

    public function __construct(
        private readonly ContainerInterface     $container,
        private readonly RouteParameterResolver $parameterResolver,
        private readonly LocaleService          $localeService,
        private readonly RoutePatternGenerator  $patternGenerator,
    ) {}

    /**
     * Registers routes by scanning controllers and their methods for route attributes.
     *
     * @param array $controllers
     * @throws ReflectionException
     */
    public function registerRoutes(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflection = new ReflectionClass($controller);
            $groupAttributes = $this->getGroupAttributes($reflection);
            $groupPath = $groupAttributes ? rtrim($groupAttributes[0]->newInstance()->path, '/') : '';
            $groupPriority = $groupAttributes ? $groupAttributes[0]->newInstance()->priority : 0;

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    $route->groupPath = $groupPath;
                    $route->priority = $groupPriority + $route->priority;
                    $route->locales = $route->locales ?: $this->localeService->getLocales();
                    $this->normalizeRouteLocale($route);

                    $this->routes[] = $this->buildRoute($route, $controller, $method->getName());
                }
            }
        }

        usort($this->routes, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    private function getGroupAttributes(ReflectionClass $reflection): array
    {
        return $reflection->getAttributes(RouteGroup::class);
    }

    private function normalizeRouteLocale(Route $route): void
    {
        if (!empty($route->locales)) {
            $this->patternGenerator->setAlias('locale', implode('|', $route->locales));

            if (!preg_match('/{locale(\?)?}/', $route->path)) {
                $route->path = '/{locale?}' . $route->path;
            }
        }
    }

    private function buildRoute(Route $route, string $controller, string $action): array
    {
        return [
            'pattern' => $this->patternGenerator->generate($route),
            'path' => $route->path,
            'group_path' => $route->groupPath,
            'locales' => $route->locales,
            'methods' => array_map('strtoupper', $route->methods),
            'controller' => $controller,
            'action' => $action,
            'name' => $route->name,
            'priority' => $route->priority,
        ];
    }

    /**
     * @throws NotFoundHttpException
     */
    public function dispatch(string $requestUri, string $requestMethod): void
    {
        foreach ($this->routes as $route) {
            if (($matches = $this->matchRoute($route, $requestUri, $requestMethod)) !== null) {
                $route['route'] = $matches[0];
                $route['params'] = $this->resolveRouteParams($matches);
                $this->current = $route;
                return;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * @throws MethodNotAllowedException
     */
    private function matchRoute(array $route, string $requestUri, string $requestMethod): ?array
    {
        if (in_array(strtoupper($requestMethod), $route['methods'], true) === false) {
            throw new MethodNotAllowedException();
        }

        if (preg_match($route['pattern'], $requestUri, $matches)) {
            return $matches;
        }

        return null;
    }

    private function resolveRouteParams(array $matches): array
    {
        $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

        if (empty($params['locale']) && !empty($this->localeService->getDefaultLocale())) {
            $params['locale'] = $this->localeService->getDefaultLocale();
        }

        return $params;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function invokeController(): void
    {
        $controller = $this->container->get($this->current['controller']);
        $methodReflection = $this->parameterResolver
            ->setController($controller)
            ->setMethodName($this->current['action'])
            ->setParams($this->current['params'])
            ->resolve();
        $methodReflection->invokeArgs($controller, $this->parameterResolver->getOrderedParams());
    }

    public function setRoutes(array $routes): static
    {
        $this->routes = $routes;
        return $this;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getCurrent(): array
    {
        return $this->current;
    }

    public function setDefaultLocale(string $locale): static
    {
        $this->localeService->setDefaultLocale($locale);
        return $this;
    }

    public function setLocales(array $locales): static
    {
        $this->localeService->setLocales($locales);
        return $this;
    }

    public function setAlias(string $alias, string $pattern): static
    {
        $this->patternGenerator->setAlias($alias, $pattern);
        return $this;
    }

    public function getAliases(): array
    {
        return $this->patternGenerator->getAliases();
    }
}
