<?php
declare(strict_types=1);

namespace AttributeRouter;

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
    private RoutePatternGenerator $patternGenerator;
    private RouteParameterResolver $parameterResolver;

    public function __construct(
        private readonly ContainerInterface $container,
        RoutePatternGenerator               $patternGenerator = null,
        RouteParameterResolver              $parameterResolver = null
    )
    {
        $this->patternGenerator = $patternGenerator ?? new RoutePatternGenerator();
        $this->parameterResolver = $parameterResolver ?? new RouteParameterResolver($container);
    }

    /**
     * @throws ReflectionException
     */
    public function registerRoutes(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflection = new ReflectionClass($controller);
            $prefix = '';
            /** @var RouteGroup $prefix_route */
            if (!empty($prefix_route = $reflection->getAttributes(RouteGroup::class))) {
                $prefix_route = $prefix_route[0]->newInstance();
                $prefix = rtrim($prefix_route->path, '/');
            }

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();
                    $this->routes[] = [
                        'pattern' => $this->patternGenerator->generate($route, $prefix),
                        'path' => $route->path,
                        'prefix' => $prefix,
                        'methods' => array_map('strtoupper', $route->methods),
                        'controller' => $controller,
                        'action' => $method->getName(),
                        'name' => $route->name
                    ];
                }
            }
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundHttpException
     */
    public function dispatch(string $requestUri, string $requestMethod): void
    {
        foreach ($this->routes as $route) {
            if (in_array(strtoupper($requestMethod), $route['methods']) &&
                preg_match($route['pattern'], $requestUri, $matches)) {
                $controller = $this->container->get($route['controller']);
                $route['route'] = $matches[0];
                $route['params'] = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
                $methodReflection = $this->parameterResolver
                    ->setController($controller)
                    ->setMethodName($route['action'])
                    ->setParams($route['params'])
                    ->resolve();

                $this->current = $route;
                $methodReflection->invokeArgs($controller, $this->parameterResolver->getOrderedParams());
                return;
            }
        }

        throw new NotFoundHttpException('Page not found');
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