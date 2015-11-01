<?php
namespace Pails;

define('kPailsAlerts', '__pails_alerts');

class Controller
{
	public $areas;
	public $view;
	private $view_path;
	private $current_alerts;
	public $model;
	public $layout;

	public static function getInstance($controller_name, $areas)
	{
		$controller_path = self::get_path_for('controller', $controller_name, $areas);

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
			if (Application::environment() == 'production')
				echo 'Sorry, something went wrong';
			else
				echo 'The controller ' . $controller_name . ' does not extend Pails\Controller.';
			exit();
		}

		//Initialize stuff
		$controller->layout = 'views/_layout.php';
		$controller->areas = $areas;

		return $controller;
	}

	private static function get_path_for($type, $path, $areas)
	{
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
		$base = $type.'s/'.$path.'.php';
		if (file_exists($base))
			return $base;

		$directories = array_reverse($areas);

		foreach ($directories as $dir) {
			if (file_exists($dir.'/'.$base))
				return $dir.'/'.$base;
		}

		header('HTTP/1.1 404 File Not Found');
		if (Application::environment() == 'production')
			echo "Sorry, there's nothing here. It's possible something went wrong, or it's possible that this URL doesn't exist. Please go back and try again.";
		else
			echo 'The ' . $type . ' ' . $path . ' does not exist.';
		exit();
	}

	public function __construct ()
	{
		if (isset($_SESSION[kPailsAlerts]) && is_array($_SESSION[kPailsAlerts])) {
			$this->current_alerts = $_SESSION[kPailsAlerts];
			unset($_SESSION[kPailsAlerts]);
		} else {
			$this->current_alerts = [];
		}
	}

	public function flash($key = null, $value = null)
	{
		if ($key != null && $value != null) {
			if (!isset($_SESSION[kPailsAlerts]))
				$_SESSION[kPailsAlerts] = [];
			$_SESSION[kPailsAlerts][$key] = $value;
		}
		return $this->current_alerts;
	}

	public function flashNow($key, $value)
	{
		if ($key != null && $value != null) {
			$this->current_alerts[$key] = $value;
		}
	}

	public function render_page()
	{
		$this->view_path = self::get_path_for('view', $this->view, $this->areas);

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

		include(self::get_path_for('view', $path, $this->areas));
	}

	protected function redirect($url)
	{
		return new RedirectResult($url);
	}

	protected function notFound($message = null)
	{
		return new NotFoundResult($message);
	}

	protected function view($view = null)
	{
		return new ViewResult($this, $view);
	}

	protected function content($content)
	{
		return new ContentResult($content);
	}

	protected function json($object)
	{
		return new JsonResult($object);
	}

	public function do_before_actions($action)
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
				if (isset($value) && isset($value['except']) && in_array($action, $value['except']))
					continue;
				if (isset($value) && isset($value['only']) && !in_array($action, $value['only']))
					continue;

				if (isset($value) && isset($value['options']))
					$this->$key($value['options']);
				else
					$this->$key();
			}
		}
	}

	public function do_after_actions($action)
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
				if (isset($value) && isset($value['except']) && in_array($action, $value['except']))
					continue;
				if (isset($value) && isset($value['only']) && !in_array($action, $value['only']))
					continue;

				if (isset($value) && isset($value['options']))
					$this->$key($value['options']);
				else
					$this->$key();
			}
		}
	}

	protected function csrf_hidden_field()
	{
		return "<input type=\"hidden\" name=\"csrf-token\" value=\"".$this->csrf_token()."\" />";
	}

	protected function csrf_token()
	{
		$tok = hash('sha512', $_SERVER['REQUEST_URI'].':'.date('U'));
		$_SESSION['csrf-token'] = $tok;
		$_SESSION['csrf-referrer'] = $_SERVER['REQUEST_URI'];
		return $tok;
	}

	protected function verify_csrf()
	{
		if (!isset($_SESSION['csrf-token']) || $_SESSION['csrf-token'] == '')
			return false;

		if (!isset($_POST['csrf-token']) || $_POST['csrf-token'] == '')
			return false;

		return $_POST['csrf-token'] === $_SESSION['csrf-token'];
	}

	public function is_https()
	{
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ||
			   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
	}

	public function require_https()
	{
		if (\Pails\Application::environment() != 'production')
			return; //We don't care in development

		if (!$this->is_https())
		{
			if ($_SERVER['REQUEST_METHOD'] == 'GET')
			{
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
				exit;
			}
			else
			{
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], true, 307);
				exit;
			}
		}
	}
}
