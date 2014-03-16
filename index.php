<?php
// First things first. Figure out what environment we're running in
$ENV = 'development';
if (file_exists('.environment'))
	$ENV = trim(file_get_contents('.environment'));

//Pull in the rest of the library
require_once(__DIR__.'/lib/Controller.php');

//Change directory to the webroot
chdir($_SERVER['DOCUMENT_ROOT']);

function console_log($obj) {
	global $ENV;
	if ($ENV == 'production') return;
	$stdout = fopen('php://stdout', 'w');
    fwrite($stdout, "LOGGING: ".print_r($obj, true)."\n");
    fclose($stdout);
}

session_start();

console_log('Starting Pails request processing');

/* Include some files */
require_once('config/application.php'); //Library inclusion and setup

/* --- php-activerecord setup --- */
require_once 'lib/php-activerecord/ActiveRecord.php';
date_default_timezone_set('UTC');
 
ActiveRecord\Config::initialize(function($cfg)
{
	global $ENV;
	global $CONNECTION_STRINGS;
	$cfg->set_model_directory('models');
	$cfg->set_connections($CONNECTION_STRINGS);

	$cfg->set_default_connection($ENV);
});
/* --- End php-activerecord setup --- */

//TODO: Don't automatically include config/common.php
if (file_exists('config/common.php'))
{
	console_log('DEPRECATION NOTICE: Use of config/common.php is discouraged. Use a common ControllerBase or module.');
	require_once('config/common.php'); //User-defined functions	
}

/* Necessary functions */

//Initialize the model
$model = null;

function to_class_name($string)
{
	// underscored to upper-camelcase 
	// e.g. "this_method_name" -> "ThisMethodName" 
	return preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')",$string); 
}

function to_table_name($string)
{
	// underscored to lower-camelcase 
	// e.g. "this_method_name" -> "thisMethodName" 
	return preg_replace('/_(.?)/e',"strtoupper('$1')",$string);
}

function render_partial($path, $local_model = null)
{
	global $model;
	global $current_user;

	//Save model
	$save_model = $model;
	if ($local_model)
		$model = $local_model;

	include('views/'.$path.'.php');

	//Restore model
	$model = $save_model;
}

function uri_parts()
{
	$url = parse_url($_SERVER['REQUEST_URI']);
	return explode('/', substr($url['path'], 1));
}

function default_controller()
{
	$uri_parts = uri_parts();
	return strlen($uri_parts[0]) > 0 ? $uri_parts[0] : 'static';
}

function default_action()
{
	$uri_parts = uri_parts();
	return count($uri_parts) > 1 ? $uri_parts[1] : 'index';
}

//First, find the appropriate controller
$current_route = $ROUTES['*'];
$uri_parts = uri_parts();
if ($uri_parts[0] != '*' /* '*' is not a valid route */ && array_key_exists($uri_parts[0], $ROUTES))
	$current_route = $ROUTES[$uri_parts[0]];

$controller_name = to_class_name($current_route[0]) . 'Controller';
$action_name = $current_route[1];

include 'controllers/'.$controller_name.'.php';

$controller = new $controller_name();

//Check to ensure controller inherits from Pails\Controller
if (!is_subclass_of($controller, 'Pails\Controller'))
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'The controller ' . $controller_name . ' does not extend Pails\Controller.';
	exit();
}


$view = $current_route[0].'/'.$action_name;
$action_result = null;

//Perform the requested action
if (in_array($action_name, get_class_methods($controller)))
{
	$opts = uri_parts();
	array_shift($opts);
	array_shift($opts);
	$action_result = count($opts) ? $controller->$action_name($opts) : $controller->$action_name();
}
else
{
	header('HTTP/1.1 404 File Not Found');
	echo 'The controller ' . $controller_name . ' does not have a public method ' . $action_name . '.';
	exit();
}

if ($view)
{
	//Then, find the appropriate view and construct the "yield" method
	$view_path = 'views/'.$view.'.php';

	if (!file_exists($view_path))
	{
		header('HTTP/1.1 404 File Not Found');
		echo 'The view ' . $view_path . ' does not exist.';
		exit();
	}

	function yield()
	{
		global $view_path;
		global $logged_in;
		global $current_user;
		global $model;

		include($view_path);
	}

	//Finally, include the layout view, which should render everything
	include('views/_layout.php');
}
else
{
	echo json_encode($action_result);
}
?>