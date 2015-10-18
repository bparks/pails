<?php

namespace Pails;

class JsonResult extends ActionResult
{
	private $object;

	public function __construct($object)
	{
		$this->object = $object;
	}

	public function render()
	{
		header('Content-Type: application/json');
		echo json_encode($this->object);
	}
}
