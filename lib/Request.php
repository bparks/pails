<?php

namespace Pails;

class Request
{
	/**
	 * DEPRECATED: The name of the controller without the "Controller" part
	 */
	public $controller;

	/**
	 * The fully-qualified name of the controller that will handle this request
	 */
	public $controller_name;

	/**
	 * The name of the action method on the controller that will handle this request
	 */
	public $action;

	/**
	 * All path segments of the incoming URL
	 */
	public $raw_parts;

	/**
	 * DEPRECATED: The "opts" parts of the URL (everything except the controller
	 * and action, usually the 3rd segment onward)
	 */
	public $opts;

	/**
	 * The "area". This may be completely unused
	 */
	public $area;

	/**
	 * The ID of the resource, for requests to resources (note: this is always populated if there is an "id" parameter)
	 */
	public $id;

	/**
	 * An associative array of parameters, usually populated from routes
	 */
	public $params;
}
