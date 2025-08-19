<?php
namespace Pails;

class ResourceController extends Controller
{
	protected $actions = array();
	protected $default_action = null;
	private $last_called = '';

	function __call($name, $args)
    {
		// We can't write a "new" function, but it's a convenient pathname
        if ($name == "new") {
            $name = "_new";
		}

		if ($name == $this->last_called) {
			return $this->notFound();
		}

		$this->last_called = $name;

		// Process the given action
        $opts = count($args) > 0 && count($args[0]) > 0 ? $args[0] : array('show');
        $action = array_shift($opts);
		$action = trim($action) == '' ? 'show' : $action;

		$method_name = null;
		if (isset($this->actions[$action])) {
			$method_name = $this->actions[$action];
		} elseif ($this->default_action != null) {
			$method_name = $this->actions[$this->default_action];
		} else {
			return $this->notFound();
		}

		$this->do_before_actions($action);
		$ret = $this->$method_name($name, $opts);
		$this->do_after_actions($action);
		return $ret;
    }
}
