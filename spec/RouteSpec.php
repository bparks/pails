<?php
include (__DIR__."/../lib/Application.php");
include (__DIR__."/../lib/Request.php");
include (__DIR__."/../lib/Utilities.php");

describe('A route', function() {

	$app = new Pails\Application(array(
		'routes' => array(
			'*' => array(false, false)
		),
		'app_name' => 'default'
	));
	$request = null;

    it('is defined as an associative array', function() use ($app, $request) {
    	$request = $app->requestForUri("/");
        expect($request)->not()->toBe(null);
    });

    it('will default to controller = app_name and action = index', function () use ($app, $request) {
    	$request = $app->requestForUri("/");
        expect($request->controller)->toBe('default');
        expect($request->action)->toBe('index');
    });

    it('will follow the pattern /controller/action', function () use ($app, $request) {
    	$request = $app->requestForUri("/test/myaction");
        expect($request->controller)->toBe('test');
        expect($request->action)->toBe('myaction');
    });
});

describe('A route definition', function() {

	$app = new Pails\Application(array(
		'routes' => array(
			'dashboard' => array('controller', 'action'),
			'api' => array('proxy', false),
			'*' => array(false, false)
		),
		'app_name' => 'default'
	));
	$request = null;

	it ('is case sensitive', function () use ($app, $request) {
		$request = $app->requestForUri("/Dashboard");
        expect($request->controller)->not()->toBe('controller');
        expect($request->action)->not()->toBe('action');
	});

	it ('can specify explicit controller actions for a top-level path', function () use ($app, $request) {
		$request = $app->requestForUri("/dashboard");
        expect($request->controller)->toBe('controller');
        expect($request->action)->toBe('action');
	});

	it ('can specify an explicit controller and implicit actions for a top-level path', function () use ($app, $request) {
		$request = $app->requestForUri("/api/endpoint");
        expect($request->controller)->toBe('proxy');
        expect($request->action)->toBe('endpoint');
	});
});

describe('A more advanced route definition', function () {

	$app = new Pails\Application(array(
		'routes' => array(
			'dashboard' => array('controller', 'action'),
			'api' => array(
				'widgets' => array('widgets_api', false),
				'v1' => array(
					'admin' => array(
						'widgets' => array('widgets_admin', false)
					),
					'widgets' => array('api\v1\widgets', false),
					'users' => array('Api\V1\Users', false),
				)
			),
			'*' => array(false, false)
		),
		'app_name' => 'default'
	));
	$request = null;

	it ('can specify nested routes', function () use ($app, $request) {
		$request = $app->requestForUri("/api/widgets/endpoint");
        expect($request->controller)->toBe('widgets_api');
        expect($request->controller_name)->toBe('WidgetsApiController');
        expect($request->action)->toBe('endpoint');
	});

	it ('can be very deeply nested', function () use ($app, $request) {
		$request = $app->requestForUri("/api/v1/admin/widgets/add");
        expect($request->controller)->toBe('widgets_admin');
        expect($request->controller_name)->toBe('WidgetsAdminController');
        expect($request->action)->toBe('add');
	});

	it ('can refer to classes in namespaces', function () use ($app, $request) {
		$request = $app->requestForUri("/api/v1/widgets/add");
        expect($request->controller)->toBe('api\v1\widgets');
        expect($request->controller_name)->toBe('Api\V1\WidgetsController');
        expect($request->action)->toBe('add');
	});

	it ('can refer to classes in namespaces in a case insensitive way', function () use ($app, $request) {
		$request = $app->requestForUri("/api/v1/users/add");
        expect($request->controller)->toBe('Api\V1\Users');
        expect($request->controller_name)->toBe('Api\V1\UsersController');
        expect($request->action)->toBe('add');
	});
});