# Xesau\Router
Xesau\Router is a one-file PHP router.

## How to use

    <?php
    
    require_once 'vendor/Autoload.php';
    
    use Xesau\Router;
    
    $router = new Router(function () {
        // 404 error handler
        include 'views/error.html';
    });
    
    $router->get('/', function() {
        // Home page
        include 'views/home.html';
    });
    
    $router->get('/page/(.*)', ['PageController', 'viewPage']);
    
    $router->dispatchGlobals();
    
