<?php

namespace Pails;

/**
* Class that manages the pails application lifecycle
*/
class Application
{
    /**
    * @ignore
    */
    private $connection_strings;
    /**
    * @ignore
    */
    private $routes;
    /**
    * @ignore
    */
    private $app_name;
    /**
    * @ignore
    */
    private $unsafe_mode;
    /**
    * @ignore
    */
    private $areas;
    /**
    * @ignore
    */
    private $yield;
    /**
    * @ignore
    */
    private $routers;

    /**
    * @ignore
    */
    private static $environment = '';
    /**
    * @ignore
    */
    private static $configuration = null;
    /**
    * @ignore
    */
    private static $environments = array();

    /**
    * Get the current environment for the running pails app.
    *
    * An environment is simply a collection of configurations and defaults.
    * Common environments include development (used for local development on a
    * machine dedicated for that purpose), test (used for the running of unit
    * or integration tests), staging (used to simulate a production environment),
    * and production. This may be configured in a `.environment` file at the root
    * of the application.
    *
    * The current environment defaults to "development".
    *
    * One particularly useful environment-based default is that in production,
    * 404s and 500s result in very generic error messages, while in other environments
    * those errors give more detail as to what is missing and/or a stack trace.
    *
    * @return string The current environment.
    */
    public static function environment()
    {
        if (self::$environment == '')
        {
            self::$environment = 'development';
            if (file_exists('../.environment')) { //Prefer it to be in the root...
                self::$environment = trim(file_get_contents('../.environment'));
            } elseif (file_exists('.environment')) {
                self::$environment = trim(file_get_contents('.environment'));
            }
        }
        return self::$environment;
    }

    /**
    * Write some information to the error log
    *
    * @param mixed $obj a value of any type to be written to the log. This
    *     function uses `print_r` under the hood to automatically format objects
    */
    public static function log($obj)
    {
        if (self::environment() == 'development')
        {
            $stdout = fopen('php://stdout', 'w');
        	fwrite($stdout, "LOGGING: ".print_r($obj, true)."\n");
        	fclose($stdout);
        }
        else
        {
            error_log("Application::log(): ".print_r($obj, true));
        }
    }

    /**
    * Set the default configuration, or one for a specific environment
    *
    * With one argument, this function expects an array and sets the default
    * configuration. With two arguments, this function expects a string and an
    * array and sets the configuration for a specific environment. The
    * settings appropriate for the current environment are resolved at runtime.
    *
    * This function will emit a warning to the log if an environment/default is
    * defined multiple times. However, the last definition always wins (no
    * merging is attempted)
    *
    * @param mixed $arg1 A string (specifying an environment) or an array
    *     (representing the default configuration).
    * @param array $arg2 An array representing the configuration for the
    *     specified environment. This argument must be present if and only if
    *     $arg1 is a string.
    */
    public static function configure($arg1, $arg2 = null)
    {
        if ($arg2 == null) {
            if (is_array($arg1)) {
                if (self::$configuration != null) {
                    self::log('Application already has a default configuration');
                }
                self::$configuration = $arg1;
            } else {
                self::log('When called with one argument, Application::configure expects an array.');
            }
        } else {
            if (is_array($arg2) && is_string($arg1)) {
                if (isset(self::$environments[$arg1])) {
                    self::log('Application already has a configuration for environment '.$arg1);
                }
                self::$environments[$arg1] = $arg2;
            } else {
                self::log('When called with two arguments, Application::configure expects a string and an array.');
            }
        }
    }

    /**
    * Gets the current configuration, based on current environment
    *
    * Merges the default configuration with the configuration for the current
    * environment.
    *
    * @return array Full configuration for the current environment
    */
    public static function config()
    {
        $config = array();
        if (self::$configuration != null) {
            $config = array_merge($config, self::$configuration);
        }
        if (isset(self::$environments[self::$environment]) && self::$environments[self::$environment] != null) {
            $config = array_merge($config, self::$environments[self::$environment]);
        }
        return $config;
    }

    /**
    * @ignore
    */
    public function __construct($args)
    {
        if (array_key_exists('connection_strings', $args))
            $this->connection_strings = $args['connection_strings'];
        if (array_key_exists('routes', $args))
            $this->routes = $args['routes'];
        if (array_key_exists('app_name', $args))
            $this->app_name = $args['app_name'];
        if (array_key_exists('unsafe_mode', $args))
            $this->unsafe_mode = $args['unsafe_mode'];

        $this->routers = array();
    }

    /**
    * @ignore
    */
    public function connection_strings()
    {
        return $this->connection_strings;
    }

    /**
    * Initializes the list of "areas"; that is, composer plugins that contain
    * controllers, models, or views. This should not be called directly.
    */
    public function load_areas()
    {
        //This actually has become an "Area"-loading function since
        //dependencies should be managed by composer
        $this->areas = array();

        $this->each_directory('../vendor', function ($path)
        {
            $this->each_directory($path, function ($item)
            {
                if ((file_exists($item.'/models') && is_dir($item.'/models')) ||
                    (file_exists($item.'/views') && is_dir($item.'/views')) ||
                    (file_exists($item.'/controllers') && is_dir($item.'/controllers')))
                    $this->areas[] = $item;
            });
        });
    }

    /**
    * Runs files in the `app/initializers` directory (in alphabetical order).
    * Initialization occurs after plugins have been loaded but before the request
    * is handled. This function should not be called directly.
    */
    public function initialize()
    {
        if (!file_exists('initializers')) return;

        $this->each_file('initializers', function ($item)
        {
            if (preg_match('/.php$/i', $item))
            {
                $initializer = function ($app) use ($item)
                {
                    include($item);
                };
                $initializer($this);
            }
        });
    }

    /**
    * @ignore
    */
    public function run()
    {
        $request = $this->requestForUri($_SERVER['REQUEST_URI']);
        if ($request == null) {
            $this->respond404();
            return;
        }
        $controller = $request->area != null ?
            Controller::getInstance($request->controller_name, $this->areas) :
            Controller::getInstance($request->controller_name, $request->area);

        // This is where I stopped refactoring
        $controller->view = $request->controller.'/'.$request->action;
        $action_result = null;

        $functions = array();
        $has_call_method = false;
        // Use reflection class to extract valid methods strictly on the controller
        $reflection_class = new \ReflectionClass($controller);
        $class_methods = $reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach($class_methods as $function)
        {
            if ($function->class === get_class($controller)) // no superclass methods
                $functions[] = $function->name;
            if ($function->name == "__call")
                $has_call_method = true;
        }

        //Perform the requested action
        if (in_array($request->action, $functions) ||
            in_array(Utilities::dashesToUnderscores($request->action), $functions) ||
            in_array(Utilities::dashesToMethodName($request->action), $functions)
        ) {
            $controller->do_before_actions($request->action);

            //Figure out WHICH of the valid action names we are using
            $action_name = $request->action;
            if (in_array(Utilities::dashesToUnderscores($request->action), $functions))
                $action_name = Utilities::dashesToUnderscores($request->action);
            elseif (in_array(Utilities::dashesToMethodName($request->action), $functions))
                $action_name = Utilities::dashesToMethodName($request->action);

            $opts = $request->opts;
            array_shift($opts);
            array_shift($opts);
            $action_result = count($opts) ? $controller->$action_name($opts) : $controller->$action_name();

            $controller->do_after_actions($request->action);
        } elseif (is_subclass_of($controller, '\Pails\ResourceController')) {
            $action_name = $request->action;
            $opts = $request->opts;
            array_shift($opts);
            array_shift($opts);
            $action_result = $controller->$action_name($opts);
        } elseif ($has_call_method) {
            //This case is still required for some plugins
            //The __call method is responsible for calling do_before_actions and do_after_actions
            $action_name = $request->action;
            $opts = $request->raw_parts;
            array_shift($opts);
            array_shift($opts);
            $action_result = count($opts) ? $controller->$action_name($opts) : $controller->$action_name();
        } else {
            $this->respond404('The controller ' . $request->controller_name . ' does not have a public method ' . $request->action . '.');
        }

        if (is_subclass_of($action_result, '\Pails\ActionResult')) {
            $action_result->render();
            return;
        } elseif (is_int($action_result)) {
            Application::log('Returning an HTTP status code from an action is deprecated. Use $this->redirect(_path_) or $this->notFound() instead.');
            if ($action_result == 404) {
                $this->respond404();
            } else if ($action_result == 302) {
                $result = new RedirectResult($controller->model);
                $result->render();
            }
        }

        if ($controller->view)
        {
            Application::log('Specifying the view on the controller is deprecated. Use a ViewResult (or $this->view()) instead.');
            $controller->render_page();
        }
        else
        {
            Application::log('Setting view to false and returning an object is deprecated. Use a JsonResult (or $this->json()) instead.');
            echo json_encode($action_result);
        }
    }

    /**
    * Register a function that will convert a URI to a {@link \Pails\Request}. This
    * enables the possibility of custom routign rules.
    *
    * Routers are evaluated prior to the pails native routing table (the value
    * of `$ROUTES` in `config/application.php`).
    *
    * @param callable $func A function or other callable that takes a single
    *     string argument (the path component of `$_SERVER['REQUEST_URI']`) and
    *     returns a {@link \Pails\Request} object.
    */
    public function registerRouter($func)
    {
        array_unshift($this->routers, $func);
    }

    /**
    * @ignore
    */
    private function each_file($path, $func)
    {
        $file_list = scandir($path);
        foreach ($file_list as $entry)
        {
            $item = $path.'/'.$entry;

            if (!is_file($item))
                continue; //Don't care about non-directories

            $func($item);
        }
    }

    /**
    * @ignore
    */
    private function each_directory($path, $func)
    {
        if ($dir = opendir($path))
        {
            while (false !== ($entry = readdir($dir)))
            {
                $item = $path.'/'.$entry;

                if (!is_dir($item))
                    continue; //Don't care about non-directories
                if ($entry == '.' || $entry == '..')
                    continue; //Really? This should be a no-brainer

                $func($item);
            }
        }
    }

    /**
    * @ignore
    */
    private function respond404($message = null)
    {
        $result = new NotFoundResult($message);
        $result->render();
    }

    /**
    * @ignore
    */
    public function requestForUri($uri, $routes = null)
    {
        if ($routes == null)
            $routes = $this->routes;

        $url = parse_url($uri);
        $request = null;
        $raw_parts = preg_split('@/@', substr($url['path'], 1), NULL, PREG_SPLIT_NO_EMPTY);
        $opts = $raw_parts;

        foreach ($this->routers as $router) {
            $req = $router($url['path']);
            if (!$req) continue;
            $request = $req;
            break;
        }

        if ($request == null)
        {
            //First, find the appropriate controller

            //if a default is specified, start with it
            $current_route = null;
            if (array_key_exists('*', $routes))
                $current_route = $routes['*'];
            if (count($opts) > 0 && $opts[0] != '*' /* '*' is not a valid route */
                && array_key_exists($opts[0], $routes))
                $current_route = $routes[$opts[0]];

            if ($current_route == null)
            {
                return null;
            }
            if (is_array($current_route) && is_string(key($current_route)))
            {
                array_shift($opts);
                $request = $this->requestForUri('/'.implode('/', $opts), $current_route);
            }
            else
            {
                $request = new Request();
                $request->opts = $opts;

                if ($current_route[0] === false)
                    $request->controller = $this->default_controller($request, $raw_parts);
                else
                    $request->controller = $current_route[0];

                if ($current_route[1] === false)
                    $request->action = $this->default_action($request, $raw_parts);
                else
                    $request->action = $current_route[1];

                if (isset($current_route[2]))
                    $request->area = $current_route[2];
            }
        }

        if ($request != null)
        {
            $request->raw_parts = $raw_parts;
            $request->controller_name = Utilities::dashesToClassName($request->controller) . 'Controller';
        }

        return $request;
    }

    /**
    * @ignore
    */
    private function default_controller($request, $uri_parts)
    {
        return count($uri_parts) > 0 && $uri_parts[0] != '' ? $uri_parts[0] : $this->app_name;
    }

    /**
    * @ignore
    */
    private function default_action($request, $uri_parts)
    {
        return count($uri_parts) > 1 && $uri_parts[1] != '' ? $uri_parts[1] : 'index';
    }
}
