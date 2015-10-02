<?php
namespace Pails;

class ResourceController extends Controller
{
	protected $actions = array();
	protected $default_action = null;

	function __call($name, $args)
    {
		// We can't write a "new" function, but it's a convenient pathname
        if ($name == "new") {
            return $this->_new();
		}

		// Process the given action
        $opts = count($args) > 0 ? $args[0] : array('show');
        $action = array_shift($opts);

		if (isset($this->actions[$action]))
		{
			$method_name = $actions[$action];
			return $this->$method_name($name, $opts);
		}

		if ($default_action != null)
		{
			$method_name = $actions[$default_action];
			return $this->$method_name($name, $opts);
		}

        return 404;
    }
}
