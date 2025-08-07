<?php

namespace Pails;

class Router
{
    public function __construct(Application $app)
    {
        if ($app == null)
            throw new \Exception("Must provide an application object to Pails::Router::__construct()");

        $app->registerRouter([$this, 'resolve']);
    }

    public function resolve ($uri, $method = null)
    {
        if ($method == null)
            $method = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->routes as $key => $value) {
            // $value is passed in so that route can (potentially) override options
            // specified by the route config
            if (self::isMatchFor(explode(':', $key, 2), [strtoupper($method), strtolower($uri)], $value)) {
                $req = new \Pails\Request();
                $req->controller = str_replace('Controller', '', $value['controller']);
                $req->controller_name = $value['controller'];
                $req->action = $value['action'];
                $req->raw_parts = self::splitSegments($uri, true);
                $req->opts = array_values($value);
                $req->params = $value;
                if (isset($req->params['id']))
                    $req->id = $req->params['id'];
                return $req;
            }
        }

        return false;
    }

    public function get ($pattern, $defaults)
    {
        $this->addRoute($pattern, 'GET', $defaults);
    }
    
    public function post ($pattern, $defaults)
    {
        $this->addRoute($pattern, 'POST', $defaults);
    }
    
    public function put ($pattern, $defaults)
    {
        $this->addRoute($pattern, 'PUT', $defaults);
    }
        
    public function patch ($pattern, $defaults)
    {
        $this->addRoute($pattern, 'PATCH', $defaults);
    }
            
    public function delete ($pattern, $defaults)
    {
        $this->addRoute($pattern, 'DELETE', $defaults);
    }

    public function resource ($pattern, $defaults)
    {
        // GET {pattern} -> index page
        // GET {pattern}/new -> _new()
        // POST {pattern}, {pattern}/new -> create, which is really _new()
        // GET {pattern}/{id} -> show
        // GET {pattern}/{id}/edit -> edit
        // POST {pattern}/{id}, {pattern}/{id}/edit -> update, which is really edit()
        $this->addRoute($pattern,                 'GET',    array_merge($defaults, ['action' => 'index']));
        $this->addRoute($pattern,                 'POST',   array_merge($defaults, ['action' => '_new']));
        $this->addRoute("$pattern/new",           'GET',    array_merge($defaults, ['action' => '_new']));
        $this->addRoute("$pattern/new",           'POST',   array_merge($defaults, ['action' => '_new']));
        $this->addRoute("$pattern/{id}",          'GET',    array_merge($defaults, ['action' => 'show']));
        $this->addRoute("$pattern/{id}",          'POST',   array_merge($defaults, ['action' => 'edit']));
        $this->addRoute("$pattern/{id}",          'DELETE', array_merge($defaults, ['action' => 'delete']));
        $this->addRoute("$pattern/{id}/delete",   'POST',   array_merge($defaults, ['action' => 'delete']));
        $this->addRoute("$pattern/{id}/{action}", 'GET',    $defaults);
        $this->addRoute("$pattern/{id}/{action}", 'POST',   $defaults);
    }

    public function addRoute($pattern, $method, $defaults)
    {
        $key = strtoupper($method).':'.strtolower($pattern);

        if (isset($this->routes[$key]))
            throw new Exception("Registering duplicate pattern!");

        //$defaults['method'] = strtoupper($method);
        $this->routes[$key] = $defaults;
    }

    public static function isMatchFor(array $full_pattern, array $full_actual, &$opts = []): bool
    {
        if ($full_pattern[0] !== $full_actual[0])
            return false;

        $pattern = $full_pattern[1];
        $actual = $full_actual[1];

        if ($pattern == $actual)
            return true;

        $pattern_segments = self::splitSegments($pattern, true);
        $actual_segments = self::splitSegments($actual, true);

        $i = 0;
        for (; $i < count($pattern_segments); $i++) {
            if (!isset($actual_segments[$i]))
                return false;
            
            if ($pattern_segments[$i] == '**') {
                return true;
            }

            if ($pattern_segments[$i][0] == '{' && $pattern_segments[$i][-1] == '}') {
                $key = substr($pattern_segments[$i], 1, -1);
                $value = $actual_segments[$i];
                $opts[$key] = $value;
                continue;
            }
            if ($pattern_segments[$i] == '*') {
                continue;
            }
            if ($pattern_segments[$i] != $actual_segments[$i]) {
                return false;
            }
        }

        return $i == count($actual_segments);
    }

    public static function splitSegments($uri, $omit_empty_segments = false)
    {
        $segments = explode('/', $uri);

        if ($omit_empty_segments)
            $segments = array_values(array_filter($segments, fn ($x) => $x != ''));
            
        return $segments;
    }
}
