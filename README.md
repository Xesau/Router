# Xesau\Router
Xesau\Router is a one-file PHP router, suitable for web applications and RESTful APIs.
It's efficient and fast, in part because it doesn't evaluate the `callable`s the moment you add the route, but only when that route is executed.

## How to use
```php
<?php
// If you have some sort of autoloader
require_once 'vendor/Autoload.php';

// If you don't have an autoloader
require_once 'vendor/Xesau/Router.php';

use Xesau\Router;

$router = new Router(function ($method, $path, $statusCode) {
    http_response_code($statusCode);
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
```
And redirect all calls to inexistent files to /index.php

    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php [L,QSA]

## Parameters
You probably want to use parameters in your urls. An easy way to do this, is by getting the value of `$_GET` fields, but Xesau\Router provides another way of passing information through the URL.

If the paramter type fails (for example, when visiting `www.site.com/page/abcdef` when the route is `/page/(\d+)`, which requires a number), the error handler will be called.

You can make a special error page this by by adding a 'catch-all' route *after* the correct route.

```php
<?php

// ... init router ... //

// Route has a numeric parameter, caught with (\d+)
$router->get('/article/(\d+)', function($id) {
    $article = Article::findById($id);
    echo $article->content;
});

// Route has an 'everything' parameter for the username (caught with (.+), which is regex
// for 'anything at least 1 character long', and a choice parameter for the page
// (caught with (overview|friends|trophies), which means the parameter must be one of those
$router->get('/profile/(.+)/(overview|friends|trophies)', function($username, $profilePage) {
    // ... do something with $username and $profilePage ... //
});

// Error handler
$router->get('/profile/.+/(.+)', function($page) {
    echo $page .' is not a correct page on the user profile.';
});

?>
```

### Throwing errors inside route handlers
When a parameters turns out to be of an incorrect value, you can call the default error handler by throwing an HttpRequestException.

```php
<?php

use Xesau\HttpRequestException;

$router->get('/test', function() {
    throw new HttpRequestException('Page not found', 404);
});

```

## Special callback notation
If you use class methods as callbacks for your routes, your route definitions can quickly come to look like this:
```php
<?php
...

$router = new Xesau\Router('Xesau\\Website\\Controller\\ErrorPages::notFound');

$router->get('/articles/([0-9]+)/comments', 'Xesau\\Website\\Controller\\News\\Comments::load');
$router->post('/articles/([0-9]+)/comments/reply', 'Xesau\\Website\\Controller\\News\\Comments::reply');
$router->post('/articles/([0-9]+)/comments/delete/([0-9]+)', 'Xesau\\Website\\Controller\\News\\Comments::delete');
$router->edit('/articles/([0-9]+)/comments/edit/([0-9]+)', 'Xesau\\Website\\Controller\\News\\Comments::edit');
```
Long strings with `\\` separating every few words. It's hard to read, and the namespace is the same for every callback. However, there is an easier way to write those callbacks, if you pass a second 'baseNamespace' parameter to the constructor
```php
<?php
...

$router = new Xesau\Router('@ErrorPages::notFound', 'Xesau.Website.Controller');

$router->get('/articles/([0-9]+)/comments', '@News.Comments::load');
$router->post('/articles/([0-9]+)/comments/reply', '@News.Comments::reply');
$router->post('/articles/([0-9]+)/comments/delete/([0-9]+)', '@News.Comments::delete');
$router->edit('/articles/([0-9]+)/comments/edit/([0-9]+)', '@News.Comments::edit');
```
As you can see, we have replaced all the `\\` with `.`. It looks more pleasant and saves you a few characters, making your lines easier to read. We have also replaced the base namespace with an `@`. Writing route callbacks has never been easier.
