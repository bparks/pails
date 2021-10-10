<?php

namespace Pails;

class NotFoundResult extends ActionResult
{
	private $message;

	public function __construct($message = null)
	{
		$this->message = $message;
	}

	public function render()
	{
		header('HTTP/1.1 404 File Not Found');
		if ($this->message == null) {
			echo 'The page could not be found.';
		} else {
			echo $this->message;
		}
		exit();
	}
}
