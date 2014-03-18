<?php

namespace Pails;

class Application
{
	private $connection_strings;
	private $routes;
	private $yield;

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
		if (self::environment() == 'production') return;
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
	}

	public function run()
	{
		$this->initializeActiveRecord();
		$request = $this->requestForUri($_SERVER['REQUEST_URI']);
		$controller = Controller::getInstance($request->controller_name);

		// This is where I stopped refactoring
		$controller->view = $request->controller.'/'.$request->action;
		$action_result = null;

		//Perform the requested action
		if (in_array($request->action, get_class_methods($controller)))
		{
			$action_name = $request->action;
			$opts = $request->raw_parts;
			array_shift($opts);
			array_shift($opts);
			$action_result = count($opts) ? $controller->$action_name($opts) : $controller->$action_name();
		}
		else
		{
			header('HTTP/1.1 404 File Not Found');
			echo 'The controller ' . $request->controller_name . ' does not have a public method ' . $request->action . '.';
			exit();
		}

		if ($controller->view)
		{
			if (!file_exists('views/'.$controller->view.'.php'))
			{
				header('HTTP/1.1 404 File Not Found');
				echo 'The view ' . $controller->view . ' does not exist.';
				exit();
			}

			$controller->render();
		}
		else
		{
			echo json_encode($action_result);
		}
	}

	private function initializeActiveRecord()
	{
		if (!file_exists('lib/php-activerecord/ActiveRecord.php')) return;

		if (!isset($this->connection_strings))
		{
			self::log('No connection strings set. Disabling php-activerecord support.');
		}
		else
		{
			\ActiveRecord\Config::initialize(function($cfg)
			{
				$cfg->set_model_directory('models');

				$cfg->set_connections($this->connection_strings);
				$cfg->set_default_connection(Pails\Application::environment());
			});
		}
	}

	private function requestForUri($uri)
	{
		$request = new Request();

		$url = parse_url($_SERVER['REQUEST_URI']);
		$request->raw_parts = explode('/', substr($url['path'], 1));

		//First, find the appropriate controller
		$current_route = $this->routes['*'];
		if ($request->raw_parts[0] != '*' /* '*' is not a valid route */
			&& array_key_exists($request->raw_parts[0], $this->routes))
			$current_route = $this->routes[$request->raw_parts[0]];

		if ($current_route[0] === false)
			$request->controller = $this->default_controller($request);
		else
			$request->controller = $current_route[0];

		$request->controller_name = to_class_name($request->controller) . 'Controller';

		if ($current_route[1] === false)
			$request->action = $this->default_action($request);
		else
			$request->action = $current_route[1];

		return $request;
	}

	private function default_controller($request)
	{
		$uri_parts = $request->raw_parts;
		return strlen($uri_parts[0]) > 0 ? $uri_parts[0] : 'static';
	}

	private function default_action($request)
	{
		$uri_parts = $request->raw_parts;
		return count($uri_parts) > 1 ? $uri_parts[1] : 'index';
	}
}