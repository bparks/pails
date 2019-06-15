<?php

namespace Pails;

class ViewResult extends ActionResult
{
	private $controller;
	private $model;

	public function __construct($controller, $view = null, $model = null)
	{
		$this->controller = $controller;
		if ($view != null)
			$this->controller->view = $view;
		if ($model != null)
			$this->model = $model;
	}

	public function render()
	{
		// Save the old model, just in case
		$save_model = $this->controller->model;

		$this->controller->model = $this->model;
		$this->controller->render_page();

		// And restore the saved model at the end
		$this->controller->model = $save_model;
	}
}
