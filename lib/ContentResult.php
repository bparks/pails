<?php

namespace Pails;

class ContentResult extends ActionResult
{
	private $content;

	public function __construct($content)
	{
		$this->content = $content;
	}

	public function render()
	{
		header('Content-Type: text/plain');
		echo $this->content;
	}
}
