<?php
//Pull in the rest of the library
require_once(__DIR__.'/lib/Application.php');
require_once(__DIR__.'/lib/Controller.php');
require_once(__DIR__.'/lib/Request.php');

//Change directory to the webroot
chdir($_SERVER['DOCUMENT_ROOT']);

if (!file_exists('config/application.php'))
{
	Pails\Application::log('ERROR: At a minimum, config/application.php is REQUIRED');
	exit();
}

/* Include some files */
// config/application.php is really not necessary UNLESS you use a database
if (file_exists('config/application.php'))
	require_once('config/application.php'); //Library inclusion and setup

$application = new Pails\Application(array(
	'connection_strings' => isset($CONNECTION_STRINGS) ? $CONNECTION_STRINGS : array(),
	'routes' => isset($ROUTES) ? $ROUTES : array('*' => array(false, false)),
	'app_name' => isset($APP_NAME) ? $APP_NAME : 'default',
	'unsafe_mode' => isset($UNSAFE_MODE) ? $UNSAFE_MODE : false
));

try
{
	$application->load_plugins();

	//Start a session
	//NOTE: This _needs_ to happen after all classes are loaded,
	//      but (obviously) _before_ you might need the session.
	session_start();

	//Initialize the environments of any plugins that need to be initialized
	$application->init_plugins();

	$application->run();
}
catch (Exception $e)
{
	if (is_a($e, 'ActiveRecord\DatabaseException') && $application->has_plugin('installer'))
	{
		header('Location: /install');
	}
	else
	{
		header('HTTP/1.0 500 Internal Server Error');
		echo '<pre>'.$e->getMessage()."\n\tat ".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString().'</pre>';
	}
}

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
?>