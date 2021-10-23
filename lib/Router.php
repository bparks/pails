<?php

namespace Pails;

class Router
{
    public function __construct($app)
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
            if (self::isMatchFor($key, strtoupper($method).':'.strtolower($uri), $value)) {
                $req = new \Pails\Request();
                $req->controller = str_replace('Controller', '', $value['controller']);
                $req->controller_name = $value['controller'];
                $req->action = $value['action'];
                $req->raw_parts = self::splitSegments($uri, true);
                $req->opts = array_values($value);
                $req->params = $value;
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

    public function addRoute($pattern, $method, $defaults)
    {
        $key = strtoupper($method).':'.strtolower($pattern);

        if (isset($this->routes[$key]))
            throw new Exception("Registering duplicate pattern!");

        //$defaults['method'] = strtoupper($method);
        $this->routes[$key] = $defaults;
    }

    public static function isMatchFor($pattern, $actual, &$opts = []): bool
    {
        if ($pattern == $actual)
            return true;

        // [x] /auth/login -> /auth/login
        // [x] /auth/* -> /auth/login, /auth/logout
        // [x] /auth/{action} -> /auth/login
        // [x] /auth/{id}/something -> /auth/*
        // [ ] /auth/**

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
