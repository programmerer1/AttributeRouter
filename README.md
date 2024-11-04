# Example
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