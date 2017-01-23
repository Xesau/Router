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
    $router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
    
    $router->dispatchGlobal();
     
    ?>

And redirect all calls to inexistent files to /index.php

    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php [L,QSA]

## Special callback notation
If you use class methods as callbacks for your routes, your route definitions can quickly come to look like this:

    $router = new Xesau\Router('Xesau\\Website\\Controller\\ErrorPages::notFound');
    
    $router->get('/articles/([0-9]+)/comments', 'Xesau\\Website\\Controller\\News\\Comments::load');
    $router->post('/articles/([0-9]+)/comments/reply', 'Xesau\\Website\\Controller\\News\\Comments::reply');
    $router->post('/articles/([0-9]+)/comments/delete/([0-9]+)', 'Xesau\\Website\\Controller\\News\\Comments::delete');
    $router->edit('/articles/([0-9]+)/comments/edit/([0-9]+)', 'Xesau\\Website\\Controller\\News\\Comments::delete');
    
Long strings with `\\` separating every few words. It's hard to read, and the namespace is the same for every callback. However, there is an easier way to write those callbacks, if you pass a second 'baseNamespace' parameter to the constructor

    $router = new Xesau\Router('@ErrorPages::notFound', 'Xesau.Website.Controller');
    
    $router->get('/articles/([0-9]+)/comments', '@News.Comments::load');
    $router->post('/articles/([0-9]+)/comments/reply', '@News.Comments::reply');
    $router->post('/articles/([0-9]+)/comments/delete/([0-9]+)', '@News.Comments::delete');
    $router->edit('/articles/([0-9]+)/comments/edit/([0-9]+)', '@News.Comments::delete');

As you can see, we have replaced all the `\\` with `.`. It looks more pleasant and saves you a few characters, making your lines easier to read. We have also replaced the base namespace with an `@`. Writing route callbacks has never been easier.
