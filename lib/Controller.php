<?php

namespace Pails;

class Controller
{
	public $plugin_paths;
	public $view;
	private $view_path;
	public $model;
	public $layout;

	public static function getInstance($controller_name, $plugin_paths)
	{
		$controller_path = self::get_path_for('controller', $controller_name, $plugin_paths);

		if (file_exists('controllers/ControllerBase.php'))
		{
			include 'controllers/ControllerBase.php';
		}

		include $controller_path;
		$controller = new $controller_name();

		//Check to ensure controller inherits from Pails\Controller
		if (!is_subclass_of($controller, 'Pails\Controller'))
		{
			header('HTTP/1.1 500 Internal Server Error');
			echo 'The controller ' . $controller_name . ' does not extend Pails\Controller.';
			exit();
		}

		//Initialize stuff
		$controller->layout = 'views/_layout.php';

		return $controller;
	}

	private static function get_path_for($type, $path, $plugin_paths)
	{
		$base = $type.'s/'.$path.'.php';
		if (file_exists($base))
			return $base;

		$directories = array_reverse($plugin_paths);

		foreach ($directories as $dir) {
			if (file_exists('lib/'.$dir.'/'.$base))
				return 'lib/'.$dir.'/'.$base;
		}

		header('HTTP/1.1 404 File Not Found');
		echo 'The ' . $type . ' ' . $path . ' does not exist.';
		exit();
	}
	
	public function render_page()
	{
		$this->view_path = self::get_path_for('view', $this->view, $this->plugin_paths);

		//Finally, include the layout view, which should render everything
		if ($this->layout !== false && file_exists($this->layout))
		{
			include($this->layout);
		}
		else
		{
			$this->render();
		}
	}

	public function render()
	{
		include($this->view_path);
	}

	public function render_partial($path, $local_model = null)
	{
		//Save model
		$model = $this->model;
		if ($local_model)
			$model = $local_model;

		include(self::get_path_for('view', $path, $this->plugin_paths));
	}

	public function do_before_actions($request)
	{
		//Handle before actions
		if (isset($this->before_actions))
		{
			foreach ($this->before_actions as $key => $value)
			{
				if (is_int($key))
				{
					$key = $value;
					unset($value);
				}
				if (isset($value) && isset($value['except']) && in_array($request->action, $value['except']))
					continue;
				if (isset($value) && isset($value['only']) && !in_array($request->action, $value['only']))
					continue;
				
				if (isset($value) && isset($value['options']))
					$this->$key($value['options']);
				else
					$this->$key();
			}
		}
	}

	public function do_after_actions($request)
	{
		//Handle after actions
		if (isset($this->after_actions))
		{
			foreach ($this->after_actions as $key => $value)
			{
				if (is_int($key))
				{
					$key = $value;
					unset($value);
				}
				if (isset($value) && isset($value['except']) && in_array($request->action, $value['except']))
					continue;
				if (isset($value) && isset($value['only']) && !in_array($request->action, $value['only']))
					continue;
				
				if (isset($value) && isset($value['options']))
					$this->$key($value['options']);
				else
					$this->$key();
			}
		}
	}
}