# Xesau\Router
Xesau\Router is a one-file-one-class PHP router, suitable for web applications and ReST-ful APIs.
It's efficient and fast, because it doesn't evaluate the `callable`s when you add the routes, but when you execute the router.

## How to use

    <?php
    // If you have some sort of autoloader
    require_once 'vendor/Autoload.php';
     
    // If you don't have an autoloader
    require_once 'vendor/Xesau/Router.php';
     
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
     
    ?>

And redirect all calls to inexistent files to /index.php

    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php [L,QSA]
