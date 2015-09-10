<?php

namespace Xesau;

/**
 * A simple regex->callback based router that is easy in use
 *
 * @author Xesau
 * @version 1.0
 */
class Router
{

    /**
     * @var array[][] $routes The defined routes
     * @var callback  $error  The error handler, invoked as ($method, $path)
     */
    private $routes = array('GET' => array(), 'POST' => array(), 'HEAD' => array(), 'PUT' => array(), 'DELETE' => array());
    private $error;

    public function __construct(callable $error)
    {
        $this->error = $error;
    }

    /**
     * Adds a route to the specified collection
     *
     * @param string|string[] $method  The method(s) this route will react to
     * @param callable        $handler The handler
     */
    public function route($method, $regex, callable $handler)
    {
        if (!is_array($method)) {
            if ($method == '*')
                foreach ($this->routes as &$collection)
                    $collection[$regex] = $handler;
            else
                $this->routes[strtoupper($method)][$regex] = $handler;
        } else {
            foreach ($method as $m)
                $this->routes[strtoupper($m)][$regex] = $handler;
        }
        return $this;
    }

    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function get($regex, callable $handler)
    {
        $this->routes['GET'][$regex] = $handler;
        return $this;
    }

    /**
     * Adds a route to the POST route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function post($regex, callable $handler)
    {
        $this->routes['POST'][$regex] = $handler;
        return $this;
    }

    /**
     * Adds a route to the PUT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function put($regex, callable $handler)
    {
        $this->routes['PUT'][$regex] = $handler;
        return $this;
    }

    /**
     * Adds a route to the HEAD route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function head($regex, callable $handler)
    {
        $this->routes['HEAD'][$regex] = $handler;
        return $this;
    }

    /**
     * Adds a route to the DELETE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $callable The handler
     */
    public function delete($regex, callable $handler)
    {
        $this->routes['DELETE'][$regex] = $handler;
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
        if (!isset($this->routes[$method]) || !count($this->routes[$method])) {
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
                    $params = array();

                    // If the path matches the pattern
                    if (preg_match('@' . $regex . '@', $path, $params)) {
                        // Pass the params to the callback, without the full url
                        array_shift($params);
                        return call_user_func_array($callback, $params);
                    }
                }
            }
        }

        // Nothing found --> error handler
        $h = $this->error;
        return $h($method, $path);
    }

    /**
     * Dispatches the router using data from the $_SERVER global
     *
     * @return mixed Router output
     */
    public function dispatchGlobal()
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');
        return $this->dispatch($_SERVER['REQUEST_METHOD'], '/' . trim(substr($pos !== false ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'], strlen(implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/')), '/'));
    }

}
