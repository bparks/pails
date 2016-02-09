<?php
namespace Pails;

define('kPailsAlerts', '__pails_alerts');

/**
* Base class for all Pails controllers.
*/
class Controller
{
    /**
    * @ignore
    */
	public $areas;
    /**
    * @ignore
    */
	public $view;
    /**
    * @ignore
    */
	private $view_path;
    /**
    * @ignore
    */
	private $current_alerts;
    /**
    * @ignore
    */
	public $model;
    /**
    * @ignore
    */
	public $layout;

	/**
	* Used to initialize a controller with the current request. (This should not
	* be used directly by application code.)
	*
	* @param string $controller_name The full classname, including any namespace.
	* @param array $areas A list of plugins which also contain controllers.
	*
	* @return Controller A new instance of the named controller.
	*/
	public static function getInstance($controller_name, $areas)
	{
		if (is_array($areas))
			$controller_path = self::get_path_for('controller', $controller_name, $areas);
		else
			$controller_path = 'areas/'.$areas.'/controllers/'.$controller_name.'.php';

		//Take this out for version 2.0
		if (file_exists('controllers/ControllerBase.php'))
		{
			if (!defined(ControllerBase))
				\Pails\Application::log('DEPRECATED: ControllerBase will not be automatically included in future versions of Pails.');
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

	/**
	* Used to find the source file for a controller or view. (This should not
	* be used directly in application code.)
	*
	* @param string $type `controller` or `view`
	* @param string $path The name of the controller or the path of the view
	* @param array $areas A list of plugins which also contain controllers and/or views.
	*
	* @return string The path, relative to the `app` directory, of the specified resource.
	*/
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

    /**
    * @ignore
    */
	public function __construct ()
	{
		if (isset($_SESSION[kPailsAlerts]) && is_array($_SESSION[kPailsAlerts])) {
			$this->current_alerts = $_SESSION[kPailsAlerts];
			unset($_SESSION[kPailsAlerts]);
		} else {
			$this->current_alerts = [];
		}
	}

    /**
    * Add a message to the top of the next rendered view OR get the messages for
    * the current request. This follows the rails convention.
    *
    * If both arguments are provided, nothing is returned. If no arguments are
    * provided, the current messages are returned.
    *
    * @param string $key The type of message being displayed (some common values
    *     are "error", "warning", and "info")
    * @param string $value The message to display
    *
    * @return mixed Nothing, or an associative array of types to messages
    */
	public function flash($key = null, $value = null)
	{
		if ($key != null && $value != null) {
			if (!isset($_SESSION[kPailsAlerts]))
				$_SESSION[kPailsAlerts] = [];
			$_SESSION[kPailsAlerts][$key] = $value;
		}
		return $this->current_alerts;
	}

    /**
    * Add a message to the top of _this_ view. This follows the rails convention.
    *
    * @param string $key The type of message being displayed (some common values
    *     are "error", "warning", and "info")
    * @param string $value The message to display
    */
	public function flashNow($key, $value)
	{
		if ($key != null && $value != null) {
			$this->current_alerts[$key] = $value;
		}
	}

    /**
    * @ignore
    */
	public function render_page()
	{
		if (is_array($this->areas))
			$this->view_path = self::get_path_for('view', $this->view, $this->areas);
		else
			$controller_path = 'areas/'.$this->areas.'/views/'.$this->view.'.php';

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

    /**
    * Renders the appropriate view for the action that has completed. This
    * method should be called once and only once, in the layout file.
    */
	public function render()
	{
		include($this->view_path);
	}

    /**
    * Renders the specified (partial) view in place. This should only be called
    * inside of another view.
    *
    * @param string $path The path to the view, relative to the views directory
    * @param mixed $local_model A model to be used inside the partial view. If
    *     left unspecified, the value of `$this->model` is used.
    */
	public function render_partial($path, $local_model = null)
	{
		//Save model
		$model = $this->model;
		if ($local_model)
			$model = $local_model;

		include(self::get_path_for('view', $path, $this->areas));
	}

    /**
    * Get a `RedirectResult` suitable for returning from an action. This represents
    * a standard HTTP 302 or 307
    *
    * @param string $url The url to redirect to
    *
    * @return RedirectResult
    */
	protected function redirect($url)
	{
		return new RedirectResult($url);
	}

    /**
    * Get a `NotFoundResult` suitable for returning from an action. This represents
    * a standard HTTP 404.
    *
    * @param string message (Optional) A message to display in addition to the 404
    *
    * @return NotFoundResult
    */
	protected function notFound($message = null)
	{
		return new NotFoundResult($message);
	}

    /**
    * Get a `ViewResult` suitable for returning from an action. This represents
    * an HTML page generated from the specified view
    *
    * @param string $view (Optional) The view to render. If unspecified, the
    *     default (based on controller and action) is used.
    *
    * @return ViewResult
    */
	protected function view($view = null)
	{
		return new ViewResult($this, $view);
	}

    /**
    * Get a `ContentResult` suitable for returning from an action. This represents
    * sending raw content to the browser
    *
    * @param string $content The content to return to the browser.
    *
    * @return ContentResult
    */
	protected function content($content)
	{
		return new ContentResult($content);
	}

    /**
    * Get a `JsonResult` suitable for returning from an action. This represents
    * encoding the specified object as JSON and returning that to the browser
    * with the appropriate content type.
    *
    * @param mixed $object The object to JSON-encode back to the browser
    *
    * @return JsonResult
    */
	protected function json($object)
	{
		return new JsonResult($object);
	}

    /**
    * Executes methods prior to the action that will fulfill the request.
    *
    * Before actions are helpful to authenticate users, set preconditions, or
    * do any other processing that needs to happen before an action method is
    * executed. Before actions are defined as a field on the `Controller` class.
    * For instance, they can be set in a constructor by assigning to `$this->before_actions`.
    *
    * This variable is an associative array of method names (that must be members
    * of the controller, either through inheritance, traits, or direct implementation)
    * to options (itself an associative array). If all before actions must be
    * executed for all actions, `$this->before_actions` may be expressed as an
    * array of method names.
    *
    * Before actions are executed for every action on a controller according to
    * the options given:
    *   * `except` - an array of actions that should be exempt from this before
    *     action
    *   * `only` - an array of actions this before action should apply to; the
    *     remaining actions are exempt
    *   * `options` - a value to pass to the before action. The before action
    *     may take more than one argument as long as all but the first are
    *     optional.
    *
    * @param string $action The name of the action that will fulfill the current
    *     request.
    */
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

    /**
    * Executes methods after the action that will fulfill the request.
    *
    * After actions are helpful to clean up after a controller, set postconditions, or
    * do any other processing that needs to happen after an action method is
    * executed, but before the `ActionResult` is rendered. Before actions are
    * defined as a field on the `Controller` class.
    * For instance, they can be set in a constructor by assigning to `$this->after_actions`.
    *
    * This variable is an associative array of method names (that must be members
    * of the controller, either through inheritance, traits, or direct implementation)
    * to options (itself an associative array). If all after actions must be
    * executed for all actions, `$this->after_actions` may be expressed as an
    * array of method names.
    *
    * After actions are executed for every action on a controller according to
    * the options given:
    *   * `except` - an array of actions that should be exempt from this after
    *     action
    *   * `only` - an array of actions this after action should apply to; the
    *     remaining actions are exempt
    *   * `options` - a value to pass to the after action. The after action
    *     may take more than one argument as long as all but the first are
    *     optional.
    *
    * @param string $action The name of the action that will fulfill the current
    *     request.
    */
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

    /**
    * Generates the HTML for an Anti-CSRF hidden field
    *
    * Note: this calls `$this->csrf_token()` and should therefore only be called
    * once per request.
    *
    * @return string The HTML for an Anti-CSRF hidden field for the current request.
    */
	protected function csrf_hidden_field()
	{
		return "<input type=\"hidden\" name=\"csrf-token\" value=\"".$this->csrf_token()."\" />";
	}

    /**
    * Generates and saves a CSRF token for this request.
    *
    * Note: subsequent calls to this function will generate a new unique token.
    *
    * @return string The Anti-CSRF token
    */
	protected function csrf_token()
	{
		$tok = hash('sha512', $_SERVER['REQUEST_URI'].':'.date('U'));
		$_SESSION['csrf-token'] = $tok;
		$_SESSION['csrf-referrer'] = $_SERVER['REQUEST_URI'];
		return $tok;
	}

    /**
    * Validates an Anti-CSRF token that has been supplied vis POST.
    *
    * This method WILL NOT let you supply your record of the Anti-CSRF token,
    * so you can't accidentally open yourself up to CSRF if you call this method.
    *
    * This method will be a great candidate for a before action with a few tweaks.
    *
    * @return boolean Whether or not the Anti-CSRF token is validated.
    */
	protected function verify_csrf()
	{
		if (!isset($_SESSION['csrf-token']) || $_SESSION['csrf-token'] == '')
			return false;

		if (!isset($_POST['csrf-token']) || $_POST['csrf-token'] == '')
			return false;

		return $_POST['csrf-token'] === $_SESSION['csrf-token'];
	}

    /**
    * Indicates whether the current request has been made using HTTPS.
    *
    * This is resilient to the use of off-box SSL termination (e.g. by load
    * balancers), as long as the X-Forwarded-Proto header is set.
    *
    * @return boolean True if the request came over HTTPS.
    */
	public function is_https()
	{
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ||
			   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
	}

    /**
    * Forces the request to be made over HTTPS and redirects if not.
    *
    * This will correctly handle insecure POST requests by sending a 307 rather
    * than a 302.
    *
    * This only has an effect in non-development environments.
    *
    * This is a great candidate for a before action.
    */
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
