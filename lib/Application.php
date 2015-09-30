<?php

namespace Pails;

class Application
{
	private $connection_strings;
	private $routes;
	private $app_name;
	private $unsafe_mode;
	private $areas;
	private $yield;
	private $routers;

	private static $environment = '';

	public static function environment()
	{
		if (self::$environment == '')
		{
			self::$environment = 'development';
			if (file_exists('.environment'))
				self::$environment = trim(file_get_contents('.environment'));
		}
		return self::$environment;
	}

	public static function log($obj)
	{
		//if (self::environment() == 'production') return;
		$stdout = fopen('php://stdout', 'w');
	    fwrite($stdout, "LOGGING: ".print_r($obj, true)."\n");
	    fclose($stdout);
	}

	public function __construct($args)
	{
		if (array_key_exists('connection_strings', $args))
			$this->connection_strings = $args['connection_strings'];
		if (array_key_exists('routes', $args))
			$this->routes = $args['routes'];
		if (array_key_exists('app_name', $args))
			$this->app_name = $args['app_name'];
		if (array_key_exists('unsafe_mode', $args))
			$this->unsafe_mode = $args['unsafe_mode'];

		$this->routers = array();
	}

	public function connection_strings()
	{
		return $this->connection_strings;
	}

	public function load_areas()
	{
		//This actually has become an "Area"-loading function since
		//dependencies should be managed by composer
		$this->areas = array();

		$this->each_directory('../vendor', function ($path)
		{
			$this->each_directory($path, function ($item)
			{
		        if ((file_exists($item.'/models') && is_dir($item.'/models')) ||
		        	(file_exists($item.'/views') && is_dir($item.'/views')) ||
		        	(file_exists($item.'/controllers') && is_dir($item.'/controllers')))
		        	$this->areas[] = $item;
			});
		});
	}

	public function initialize()
	{
		if (!file_exists('initializers')) return;

		$this->each_file('initializers', function ($item)
		{
			if (preg_match('/.php$/i', $item))
			{
				$initializer = function ($app) use ($item)
				{
					include($item);
				};
				$initializer($this);
			}
		});
	}

	public function run()
	{
		$request = $this->requestForUri($_SERVER['REQUEST_URI']);
		$controller = Controller::getInstance($request->controller_name, $this->areas);

		// This is where I stopped refactoring
		$controller->view = $request->controller.'/'.$request->action;
		$action_result = null;

		$functions = array();
		$has_call_method = false;
		// Use reflection class to extract valid methods strictly on the controller
		$reflection_class = new \ReflectionClass($controller);
		$class_methods = $reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach($class_methods as $function)
		{
			if ($function->class === get_class($controller)) // no superclass methods
				$functions[] = $function->name;
			if ($function->name == "__call")
				$has_call_method = true;
		}

		//Perform the requested action
		if ($has_call_method || in_array($request->action, $functions))
		{
			$controller->do_before_actions($request);

			$action_name = $request->action;
			$opts = $request->raw_parts;
			array_shift($opts);
			array_shift($opts);
			$action_result = count($opts) ? $controller->$action_name($opts) : $controller->$action_name();

			$controller->do_after_actions($request);
		}
		else
		{
			header('HTTP/1.1 404 File Not Found');
			echo 'The controller ' . $request->controller_name . ' does not have a public method ' . $request->action . '.';
			exit();
		}

		if (is_int($action_result))
		{
			if ($action_result == 404)
			{
				header('HTTP/1.1 404 File Not Found');
				echo 'The page ' . $_SERVER['REQUEST_URI'] . ' could not be found.';
				exit();
			}
			else if ($action_result == 302)
			{
				header('HTTP/1.1 302 Found');
				header('Location: ' . $controller->model);
				echo 'The page has moved to ' . $controller->model;
				exit();
			}
		}

		if ($controller->view)
		{
			$controller->render_page();
		}
		else
		{
			echo json_encode($action_result);
		}
	}

	public function registerRouter($func)
	{
		array_unshift($this->routers, $func);
	}

	private function each_file($path, $func)
	{
		if ($dir = opendir($path))
		{
			while (false !== ($entry = readdir($dir)))
			{
		        $item = $path.'/'.$entry;

		        if (!is_file($item))
		        	continue; //Don't care about non-directories

		        $func($item);
		    }
		}
	}

	private function each_directory($path, $func)
	{
		if ($dir = opendir($path))
		{
			while (false !== ($entry = readdir($dir)))
			{
		        $item = $path.'/'.$entry;

		        if (!is_dir($item))
		        	continue; //Don't care about non-directories
		        if ($entry == '.' || $entry == '..')
		        	continue; //Really? This should be a no-brainer

		        $func($item);
		    }
		}
	}

	public function requestForUri($uri, $routes = null)
	{
		if ($routes == null)
			$routes = $this->routes;

		$url = parse_url($uri);
		$request = null;
		$raw_parts = explode('/', substr($url['path'], 1));

		foreach ($this->routers as $router) {
			$req = $router($url['path']);
			if (!$req) continue;
			$request = $req;
			break;
		}

		if ($request == null)
		{
			//First, find the appropriate controller

			//if a default is specified, start with it
			$current_route = null;
			if (array_key_exists('*', $routes))
				$current_route = $routes['*'];
			if ($raw_parts[0] != '*' /* '*' is not a valid route */
				&& array_key_exists($raw_parts[0], $routes))
				$current_route = $routes[$raw_parts[0]];

			if ($current_route == null)
			{
				return null;
			}
			if (is_array($current_route) && is_string(key($current_route)))
			{
				$request = $this->requestForUri('/'.implode('/', array_slice($raw_parts, 1)), $current_route);
			}
			else
			{
				$request = new Request();

				if ($current_route[0] === false)
					$request->controller = $this->default_controller($request, $raw_parts);
				else
					$request->controller = $current_route[0];

				if ($current_route[1] === false)
					$request->action = $this->default_action($request, $raw_parts);
				else
					$request->action = $current_route[1];
			}
		}

		$request->raw_parts = $raw_parts;
		$request->controller_name = Utilities::toClassName($request->controller) . 'Controller';

		return $request;
	}

	private function default_controller($request, $uri_parts)
	{
		return strlen($uri_parts[0]) > 0 ? $uri_parts[0] : $this->app_name;
	}

	private function default_action($request, $uri_parts)
	{
		return count($uri_parts) > 1 && $uri_parts[1] != '' ? $uri_parts[1] : 'index';
	}
}
