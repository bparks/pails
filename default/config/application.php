<?php
/*  Application configuration goes here.
    The two most important things are:
    $CONNECTION_STRINGS - connection strings in the format expected by
    	php-activerecord
    $ROUTES - routes. These take the form of an associative array, where
    	each key is the first part of the path (or '*' to define the
    	default route) and the value is an array of (controlelr_name,
    	action_name)
*/


$CONNECTION_STRINGS = array(
		'development' => 'mysql://localhost/mydb'
	);

$ROUTES = array(
	'*' => array(
		false,
		false
		)
	);