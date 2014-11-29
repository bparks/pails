Pails
=====

Pails is a seriously lightweight MVC framework written in PHP. The overarching
vision is that less code is more powerful (and certainly gives rise to fewer
bugs). [Read more on my blog][blog]

Pails is licensed under the [GPLv3][gplv3]. If the GPL doesn't work for you,
let's talk about your use case. If you need verification that this is free
software (for your boss, CTO, etc.), that can also be arranged.

Come talk about pails in #pails on Freenode.

Building a site with pails
--------------------------

You can build a pails app using some automated scripts OR manually. Using scripts
is probably faster.

First, install pails:

```sh
git clone https://github.com/bparks/pails.git
cd pails && make install        # Note: if you don't have permissions to
                                # /usr/local/**/, you'll need to precede
                                # the installation command with 'sudo'
```

Then, run the pails command to build out a new tree for you:

```sh
pails new my_pails_app    # Creates a new app
cd my_pails_app
pails server    # Runs the PHP development server (requires PHP 5.4+)
```

Build an app!
-------------

Models go in /models and extend ActiveRecord\Model. If you're using models, you'll
need the pails activerecord plugin:

```sh
pails install activerecord
```

Controllers go in /controllers with names like StuffController (case matters)
and extend Pails\Controller.

Views go in /views, in subfolders named by controller (all lowercase). Thus, a
view for the 'index' action of StuffController would be views/stuff/index.php.

Each *public* method in a Controller class is a valid action.

Plugins
-------

We've been using Pails over at [Synapse Software][synapse] for almost a year now,
so we have a wealth of additional functionality we're packaging up as plugins. You
too can build plugins and contribute them. Use this [test plugin][test_plugin] as
an example. If you want your plugin to be listed in the directory, submit a pull
request against the [pails-plugins][pails-plugins] repository.

Before and after actions
------------------------

Just like Rails, pails has before and after actions. Right now, they need to be
public methods, which are configured as before or after actions with a class-level
variable called `$before_actions` or `$after_actions`, like so:

```php
$before_actions = array('require_login', 'require_admin');
```

The referenced functions can't take any arguments.

You can also exclude them from beign applicable to certain actions by making this
array associative,  like so:

```php
$before_actions = array(
    'require_login',
    'require_admin' => array('except' => array('index'))
);
```

BE WARNED that in a future release, the way to do this properly will be to fiddle
with these variables (or preferably to call a method by the same name, which doesn't
exist yet) inside the class's `__construct()` function. This eleminates the problem
caused by inheritance of subclasses clobbering before and after actions that are set
by superclasses.

Questions?
----------

Send email to bparks@synapsesoftware.com.

[blog]: http://bparks.github.io/
[gplv3]: http://www.gnu.org/licenses/gpl-3.0.html
[synapse]: http://synapsesoftware.com
[test_plugin]: https://github.com/bparks/pails-test-plugin
[pails-plugins]: https://github.com/bparks/pails-plugins
