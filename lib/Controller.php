<?php

namespace Pails;

class Controller
{
	public $view;
	public $model;
	public $layout;

	public static function getInstance($controller_name, $plugin_order)
	{
		$controller_path = self::get_path_for_controller($controller_name, $plugin_order);

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

	private static function get_path_for_controller($controller_name, $plugin_order)
	{
		$base = 'controllers/'.$controller_name.'.php';
		if (file_exists($base))
			return $base;

		$directories = array_reverse($plugin_order);

		foreach ($directories as $dir) {
			if (file_exists('lib/'.$dir.'/'.$base))
				return 'lib/'.$dir.'/'.$base;
		}

		header('HTTP/1.1 500 Internal Server Error');
		echo 'Missing controller: ' . $controller_name . '.';
		exit();
	}
	
	public function render_page()
	{
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
		include('views/'.$this->view.'.php');
	}

	public function render_partial($path, $local_model = null)
	{
		//Save model
		$model = $this->model;
		if ($local_model)
			$model = $local_model;

		include('views/'.$path.'.php');
	}
}