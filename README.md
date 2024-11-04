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
    #[Route(path: '/edit/{id}/{uuid?}', methods: ['GET', 'POST'], name: 'edit', patterns: ['id' => '[0-9]+'])]
    public function edit(int $id, ?string $uuid = null)
    {
        //
    }
}
```
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