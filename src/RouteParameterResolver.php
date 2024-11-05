<?php
declare(strict_types=1);

namespace AttributeRouter;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;

class RouteParameterResolver
{
    private object $controller;
    private string $methodName;
    private array $params;
    private array $orderedParams = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function resolve(): ReflectionMethod
    {
        $methodReflection = new ReflectionMethod($this->controller, $this->methodName);

        foreach ($methodReflection->getParameters() as $param) {
            $paramName = $param->getName();
            if (array_key_exists($paramName, $this->params)) {
                $this->orderedParams[] = $this->params[$paramName];
            } elseif ($param->hasType() && !$param->getType()->isBuiltin()) {
                $this->orderedParams[] = $this->container->get($param->getType()->getName());
            } elseif ($param->isOptional()) {
                $this->orderedParams[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Missing required parameter: $paramName");
            }
        }

        return $methodReflection;
    }

    public function setController(object $controller): static
    {
        $this->controller = $controller;
        return $this;
    }

    public function setMethodName(string $methodName): static
    {
        $this->methodName = $methodName;
        return $this;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getOrderedParams(): array
    {
        return $this->orderedParams;
    }
}