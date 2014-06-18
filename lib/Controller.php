<?php

namespace Pails;

class Controller
{
	public $view;
	public $model;

	public static function getInstance($controller_name)
	{
		if (!file_exists('controllers/'.$controller_name.'.php'))
		{
			header('HTTP/1.1 500 Internal Server Error');
			echo 'Missing controller: ' . $controller_name . '.';
			exit();
		}

		if (file_exists('controllers/ControllerBase.php'))
		{
			include 'controllers/ControllerBase.php';
		}

		include 'controllers/'.$controller_name.'.php';
		$controller = new $controller_name();

		//Check to ensure controller inherits from Pails\Controller
		if (!is_subclass_of($controller, 'Pails\Controller'))
		{
			header('HTTP/1.1 500 Internal Server Error');
			echo 'The controller ' . $controller_name . ' does not extend Pails\Controller.';
			exit();
		}

		return $controller;
	}
	
	public function render_page()
	{
		//Finally, include the layout view, which should render everything
		if (file_exists('views/_layout.php'))
		{
			include('views/_layout.php');
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