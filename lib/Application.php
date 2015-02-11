<?php

namespace Pails;

class Application
{
	private $connection_strings;
	private $routes;
	private $app_name;
	private $unsafe_mode;
	private $plugins;
	private $plugin_order;
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

	public function load_plugins()
	{
		$this->plugins = array();

		if ($dir = opendir('lib'))
		{
			while (false !== ($entry = readdir($dir)))
			{
		        if (!is_dir('lib/'.$entry) || $entry == "pails")
		        	continue; //Don't care about non-directories; avoid including pails again
		        if ($entry == '.' || $entry == '..')
		        	continue; //Really? This should be a no-brainer

		        if (file_exists('lib/'.$entry.'/.pails'))
		        {
		        	//Is a *proper* pails module
		        	$plugin_config = file_get_contents('lib/'.$entry.'/.pails');
		        	$conf_obj = json_decode($plugin_config);
		        	if ($conf_obj === null)
		        	{
		        		self::log("ERROR: lib/$entry has bad .pails file. Could not continue.");
		        		exit();
		        	}
		        	else
		        	{
		        		$this->plugins[$entry] = $conf_obj;
		        	}
		        }
		        else if (file_exists('lib/'.$entry.'/index.php'))
		        {
		        	//Is not a proper pails module, but we might be able to do something with it
		        	if ($this->unsafe_mode)
		        	{
		        		$this->plugins[$entry] = (object)array(
		        			'index' => 'index.php',
		        			'deps' => array()
		        		);
		        	}
		        	else
		        		self::log("WARNING: lib/$entry is not a safe pails plugin and was not loaded. Enable unsafe mode or ensure this plugin has a .pails file");
		        }
		        else
		        {
		        	self::log("WARNING: lib/$entry is not a pails plugin and should be removed");
		        }
		    }
		}

		//TODO: Load in dependency order
		$this->plugin_order = array();
		foreach ($this->plugins as $name => $config)
		{
			$this->load_plugin($name);
		}
	}

	private function load_plugin($name)
	{
		if (in_array($name, $this->plugin_order))
			return; //Assume that, if the plugin is loaded, its deps are, too
		foreach ($this->plugins[$name]->deps as $pname)
		{
			$this->load_plugin($pname);
		}
		require_once('lib/'.$name.'/'.$this->plugins[$name]->index);
		$this->plugin_order[] = $name;
	}

	public function init_plugins()
	{
		foreach ($this->plugin_order as $name)
		{
			$funcname = $name.'_config';
			if (function_exists($funcname))
				$funcname($this);
		}
	}

	public function has_plugin ($name)
	{
		return in_array($name, $this->plugin_order);
	}

	public function run()
	{
		$request = $this->requestForUri($_SERVER['REQUEST_URI']);
		$controller = Controller::getInstance($request->controller_name, $this->plugin_order);

		// This is where I stopped refactoring
		$controller->view = $request->controller.'/'.$request->action;
		$controller->plugin_paths = $this->plugin_order;
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

	private function requestForUri($uri)
	{
		$url = parse_url($_SERVER['REQUEST_URI']);
		$request = null;

		foreach ($this->routers as $router) {
			$req = $router($url['path']);
			if (!$req) continue;
			$request = $req;
			break;
		}

		if ($request == null)
		{
			$request = new Request();
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

			if ($current_route[1] === false)
				$request->action = $this->default_action($request);
			else
				$request->action = $current_route[1];
		}

		$request->controller_name = to_class_name($request->controller) . 'Controller';

		return $request;
	}

	private function default_controller($request)
	{
		$uri_parts = $request->raw_parts;
		return strlen($uri_parts[0]) > 0 ? $uri_parts[0] : $this->app_name;
	}

	private function default_action($request)
	{
		$uri_parts = $request->raw_parts;
		return count($uri_parts) > 1 && $uri_parts[1] != '' ? $uri_parts[1] : 'index';
	}
}
