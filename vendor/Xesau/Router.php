<?php

namespace Xesau;

use InvalidArgumentException;

/**
 * A simple regex->callback based router that is easy in use
 *
 * @author Xesau
 */
class Router
{

    /**
     * @var array[][] $routes        The defined routes
     * @var callback  $error         The error handler, invoked as ($method, $path)
     * @var string    $baseNamespace The base namespace
     * @var string    $currentPrefix The current route prefix
     */
    private $routes;
    private $error;
    private $baseNamespace;
    private $currentPrefix;
    
    /**
     * Initiates the router and sets some default values
     *
     * @param callable $error         The 404 Not Found handler
     * @param string   $baseNamespace The base namespace
     */
    public function __construct($error, $baseNamespace = '')
    {
        $this->routes = [];
        $this->error = $error;
        $this->baseNamespace = $baseNamespace == '' ? '' : $baseNamespace.'\\';
        $this->currentPrefix = '';
    }

    /**
     * Adds a route to the specified collection
     *
     * @param string|string[] $method  The method(s) this route will react to
     * @param callable        $handler The handler
     */
    public function route($method, $regex, $handler)
    {
        if ($method == '*') {
            $method = ['GET', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'POST', 'HEAD'];
        }
        
        foreach ((array)$method as $m) {
            $this->addRoute($m, $regex, $handler);
        }
        
        return $this;
    }
    
    private function addRoute($method, $regex, $handler) {
        $this->routes[strtoupper($method)][$this->currentPrefix . $regex] = $handler;
    }

    /**
     * Prefix a group of routes
     *
     * @param string $prefix The prefix
     * @param callable $routes callable(Router) wherein the mounted routes be added
     */
    public function mount($prefix, callable $routes) {
        $previous = $this->currentPrefix;
        $this->currentPrefix = $prefix;
        $routes($this);
        $this->currentPrefix = $previous;
        
        return $this;
    }
    
    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function get($regex, $handler)
    {
        $this->addRoute('GET', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the POST route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function post($regex, $handler)
    {
        $this->addRoute('POST', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the PUT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function put($regex, $handler)
    {
        $this->addRoute('PUT', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the HEAD route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function head($regex, $handler)
    {
        $this->addRoute('HEAD', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the DELETE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function delete($regex, $handler)
    {
        $this->addRoute('DELETE', $regex, $handler);
        return $this;
    }
    
    /**
     * Adds a route to the OPTIONS route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function options($regex, $handler)
    {
        $this->addRoute('OPTIONS', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the TRACE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function trace($regex, $handler)
    {
        $this->addRoute('TRACE', $regex, $handler);
        return $this;
    }

    /**
     * Adds a route to the CONNECT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function connect($regex, $handler)
    {
        $this->addRoute('CONNECT', $regex, $handler);
        return $this;
    }

    /**
     * Dispatches the router
     *
     * @param string $method The HTTP method, most likely from $_SERVER['REQUEST_METHOD']
     * @param string $path   The request path, most likely from some URL rewrite ?r=
     * @return mixed The router output
     */
    public function dispatch($method, $path)
    {
        // If there are no routes for that method, just error immediately
        if (!isset($this->routes[$method])) {
            $h = $this->error;
            return $h($method, $path);
        } else {
            // Loop over all given routes
            foreach ($this->routes[$method] as $regex => $callback) {
                if (strlen($regex) > 0) {
                    // Fix missing begin-/
                    if ($regex[0] != '/')
                        $regex = '/' . $regex;

                    // Prevent @ collision
                    $regex = str_replace('@', '\\@', $regex);

                    // If the path matches the pattern
                    if (preg_match('@^' . $regex . '$@', $path, $params)) {
                        // Pass the params to the callback, without the full url
                        array_shift($params);
                        return $this->call($callback, $params);
                    }
                }
            }
        }

        // Nothing found --> error handler
        return $this->call($this->error, [$method, $path]);
    }
    
    /**
     * Internal function to parse and call custom callables
     * 
     * @param mixed $callable string, string[] or callable to call
     * @param array $params   The parameters to send to call_user_func_array
     * @return mixed The results from the call
     */
    private function call($callable, array $params = []) {
        if (is_string($callable)) {
            if (strlen($callable) > 0) {
                if ($callable[0] == '@') {
                    $callable = $this->baseNamespace . substr($callable, 1);
                }
            } else {
                throw new InvalidArgumentException('Route/error callable as string must not be empty.');
            }
            $callable = str_replace('.', '\\', $callable);
        }
        if (is_array($callable)) {
            if (count($callable) !== 2)
                throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');
            if (strlen($callable[0]) > 0) {
                if ($callable[0][0] == '@') {
                    $callable[0] = $this->baseNamespace . substr($callable[0], 1);
                }
            } else {
                throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');
            }
            $callable[0] = str_replace('.', '\\', $callable[0]);
        }
        return call_user_func_array($callable, $params);
    }

    /**
     * Dispatches the router using data from the $_SERVER global
     *
     * @return mixed Router output
     */
    public function dispatchGlobal()
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');
        return $this->dispatch(
            $_SERVER['REQUEST_METHOD'],
            '/'. trim(
                substr($pos !== false
                ?   substr($_SERVER['REQUEST_URI'], 0, $pos)
                :   $_SERVER['REQUEST_URI'],
                strlen(implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) .'/')),
                '/'
            )
        );
    }

}
