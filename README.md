# Example
```bash
composer require attribute-router/router
```
# Controller
```php
declare(strict_types=1);

namespace Controller;

use AttributeRouter\Route;
use AttributeRouter\RouteGroup;

#[RouteGroup(path: '/admin')]
class HomeController
{
    #[Route(path: '/edit/{id}/{uuid?}', methods: ['GET'], name: 'edit', patterns: ['id' => '[0-9]+'])]
    public function edit(int $id, ?string $uuid = null)
    {
        //
    }
}
```
# Initialization
```php
require '../vendor/autoload.php';

use Controller\HomeController;
use AttributeRouter\Router;
use DI\Container;
use Controller\TestController;

$container = new Container;
$router = $container->get(Router::class);
$router->registerRoutes([
    HomeController::class,
    TestController::class,
]);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$router->dispatch($requestUri, $requestMethod);
```
It is also possible to specify languages. But it should be specified before registering the controllers.
```php
$container = new Container;
$router = $container->get(Router::class);
$router->setDefaultLocale('en')->setLocales(['en', 'ru', 'fr']);
$router->registerRoutes([
    HomeController::class,
    TestController::class,
]);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$router->dispatch($requestUri, $requestMethod);
```
Cached routes can be forwarded. This method is shown for familiarization. You can use special classes for this.
```php
$container = new Container;
$router = $container->get(Router::class);
$router->registerRoutes([
    HomeController::class,
    TestController::class,
]);

file_put_contents('cache.txt', serialize($router->getRoutes()));
```
If routes are cached, you can do without registering controllers
```php
$container = new Container;
$router = $container->get(Router::class);

$data = unserialize(file_get_contents('cache.txt'));

$router->setRoutes($data);
$router->dispatch($requestUri, $requestMethod);
```