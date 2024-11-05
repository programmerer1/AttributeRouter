<?php
declare(strict_types=1);

namespace AttributeRouter;

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
        public LocaleService                    $localeService,
        public RoutePatternGenerator            $patternGenerator,
    )
    {
    }

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
            $groupPath = $this->getGroupPath($reflection);

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    $route->groupPath = $groupPath;

                    $route->locales = $route->locales ?: $this->localeService->getLocales();
                    $this->normalizeRouteLocale($route);

                    $this->routes[] = $this->buildRoute($route, $controller, $method->getName());
                }
            }
        }
    }

    private function getGroupPath(ReflectionClass $reflection): string
    {
        $groupAttributes = $reflection->getAttributes(RouteGroup::class);
        return $groupAttributes ? rtrim($groupAttributes[0]->newInstance()->path, '/') : '';
    }

    private function normalizeRouteLocale(Route $route): void
    {
        if (!empty($route->locales)) {
            $this->patternGenerator->addAlias('locale', implode('|', $route->locales));

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
            'name' => $route->name
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
                $this->invokeController($route);
                return;
            }
        }

        throw new NotFoundHttpException('Page not found');
    }

    private function matchRoute(array $route, string $requestUri, string $requestMethod): ?array
    {
        if (in_array(strtoupper($requestMethod), $route['methods'], true) &&
            preg_match($route['pattern'], $requestUri, $matches)) {
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
    private function invokeController(array $route): void
    {
        $controller = $this->container->get($route['controller']);
        $methodReflection = $this->parameterResolver
            ->setController($controller)
            ->setMethodName($route['action'])
            ->setParams($route['params'])
            ->resolve();

        $this->current = $route;
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
}