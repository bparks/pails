<?php

namespace Pails;

class ViewResult extends ActionResult
{
	private $controller;

	public function __construct($controller, $view = null)
	{
		$this->controller = $controller;
		if ($view != null)
			$this->controller->view = $view;
	}

	public function render()
	{
		$this->controller->render_page();
	}
}
