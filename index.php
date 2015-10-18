<?php
//Pull in the rest of the library
require_once(__DIR__.'/lib/Application.php');
require_once(__DIR__.'/lib/Controller.php');
require_once(__DIR__.'/lib/ResourceController.php');
require_once(__DIR__.'/lib/Request.php');
require_once(__DIR__.'/lib/Utilities.php');
require_once(__DIR__.'/lib/ActionResult.php');
require_once(__DIR__.'/lib/RedirectResult.php');
require_once(__DIR__.'/lib/NotFoundResult.php');
require_once(__DIR__.'/lib/ViewResult.php');
require_once(__DIR__.'/lib/ContentResult.php');
require_once(__DIR__.'/lib/JsonResult.php');

//If we're running as a web app, bring us to the root of the app
if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] != '') {
    chdir($_SERVER['DOCUMENT_ROOT'].'/../app');
} else //Otherwise, bring us to the right 'app' directory
	chdir(__DIR__.'/../../../app');

/* Include some files */
// config/application.php is really not necessary UNLESS you use a database
if (file_exists('config/application.php'))
	require_once('config/application.php'); //Library inclusion and setup

//Set the default time zone
if (!isset($TIME_ZONE) || trim($TIME_ZONE) == '')
	$TIME_ZONE = 'UTC';
date_default_timezone_set($TIME_ZONE);

//If we're using composer for anything, include that now
if (file_exists('../vendor/autoload.php'))
	require_once('../vendor/autoload.php');

$application = new Pails\Application(array(
	'connection_strings' => isset($CONNECTION_STRINGS) ? $CONNECTION_STRINGS : array(),
	'routes' => isset($ROUTES) ? $ROUTES : array('*' => array(false, false)),
	'app_name' => isset($APP_NAME) ? $APP_NAME : 'default',
	'unsafe_mode' => isset($UNSAFE_MODE) ? $UNSAFE_MODE : false
));

try
{
	$application->initialize();
	$application->load_areas();

	//Start a session
	//NOTE: This _needs_ to happen after all classes are loaded,
	//      but (obviously) _before_ you might need the session.
	session_start();

	if(php_sapi_name() != 'cli')
		$application->run();
	else if (isset($argv[1]))
		include($argv[1]);
	else
		pails_repl();
}
catch (Exception $e)
{
	$long_message = $e->getMessage()."\nat ".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString();
	Pails\Application::log($long_message);
	if (is_a($e, 'ActiveRecord\DatabaseException') && file_exists('vendor/pails/installer') && $_SERVER['REQUEST_URI'] != '/install')
	{
		header('Location: /install');
	}
	else
	{
		header('HTTP/1.0 500 Internal Server Error');
		echo '<pre>'.$long_message.'</pre>';
	}
}

function pails_repl()
{
	while (($line = readline("> ")) !== false)
	{
		readline_add_history($line);

		if ($line === '')
			continue;

		if (substr($line, -1) !== ';')
			$line .= ';';

		try {
			$result = eval("return ".$line);
			echo "=> ".json_encode($result)."\n";
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	if ($line === false) echo "\n";
}
?>
