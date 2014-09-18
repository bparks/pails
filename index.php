<?php
//Pull in the rest of the library
require_once(__DIR__.'/lib/Application.php');
require_once(__DIR__.'/lib/Controller.php');
require_once(__DIR__.'/lib/Request.php');

//Change directory to the webroot
chdir($_SERVER['DOCUMENT_ROOT']);

function console_log($obj) {
	Pails\Application::log("DEPRECATION NOTICE: Use Application::log() isntead of console_log()");
	Pails\Application::log($obj);
}

//Start a session
session_start();

if (!file_exists('config/application.php'))
{
	Pails\Application::log('ERROR: At a minimum, config/application.php is REQUIRED');
	exit();
}

/* Include some files */
require_once('config/application.php'); //Library inclusion and setup

$application = new Pails\Application(array(
	'connection_strings' => $CONNECTION_STRINGS,
	'routes' => $ROUTES
));

/* --- php-activerecord setup --- */
if (file_exists('lib/php-activerecord/ActiveRecord.php'))
{
	require_once('lib/php-activerecord/ActiveRecord.php');
	date_default_timezone_set('UTC');
}
/* --- End php-activerecord setup --- */

if (file_exists('config/common.php'))
{
	Pails\Application::log('DEPRECATION NOTICE: Use of config/common.php is discouraged. Use a common ControllerBase or module.');
	require_once('config/common.php'); //User-defined functions	
}

//After this point, we have an Application in $application. We should just be
//able to call $application->run() and that will take care of the rest, but
//we're not quite there yet

try
{
	$application->run();
}
catch (Exception $e)
{
	echo '<pre>'.$e->getMessage()."\n\tat ".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString().'</pre>';
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

function render_partial($path, $local_model = null)
{
	Pails\Application::log('render_partial() is deprecated and has no behavior. Use $this->render_partial()');
}
?>