<?php

namespace Pails;

class RedirectResult extends ActionResult
{
	private $redirect_to;

	public function __construct($url)
	{
		$this->redirect_to = $url;
	}

	public function render()
	{
		header('HTTP/1.1 302 Found');
		header('Location: ' . $this->redirect_to);
		echo 'The page has moved to ' . $this->redirect_to;
		exit();
	}
}
