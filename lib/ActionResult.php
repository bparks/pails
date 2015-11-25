<?php

namespace Pails;
/**
* Represents the result of executing an action on a controller. This encapsulates
* the HTTP response and anything else that might come along with it.
*/
abstract class ActionResult
{
    /**
    * Render the result. For instance, for a ViewResult this means it writes the
    * appropriate HTML to the browser, while for a RedirectResult it sends an
    * HTTP 302 or 307.
    *
    * This function, when implemented in a subclass, primarily has side-effects.
    */
	public abstract function render();
}
