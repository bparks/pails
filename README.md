Pails
=====

Pails is a seriously lightweight MVC framework written in PHP. The overarching
vision is that less code is more powerful (and certainly gives rise to fewer
bugs). [Read more on my blog][blog]

Pails is licensed under the [GPLv3][gplv3]. If the GPL doesn't work for you,
let's talk about your use case. If you need verification that this is free
software (for your boss, CTO, etc.), that can also be arranged.

Come talk about pails in #pails on Freenode.

Quick start
-----------

1. Install pails
2. Initialize a new site
3. Configure your database
4. Install plugins (and run any necessary migrations)
5. Add your own layout
6. (Optional) Add your own functionality, using the MVC pattern

You can also download a pre-built "distribution", which is comprised of
a pristine pails app and the plugins necessary to perform a specific
purpose, such as a pre-built Content Management System. This means that
steps 1, 2, and 4 are done for you and you only need to do steps 3, 5,
and possibly 6. List coming soon.

Install pails
-------------

From a command prompt:

```sh
git clone https://github.com/bparks/pails.git
cd pails && make install        # Note: if you don't have permissions to
                                # /usr/local/**/, you'll need to precede
                                # the installation command with 'sudo'
```

Initialize a new site
---------------------

From a command prompt:

```sh
pails new my_pails_app    # Creates a new app
cd my_pails_app
pails server    # Runs the PHP development server (requires PHP 5.4+)
```

Configure your database
-----------------------

MOST sites will need a database to work properly. This is configured in the
`$CONNECTION_STRINGS` array in `config/application.php`. The format is the
same as for php-activerecord:

    dbtype://user:password@hostname/dbname

For instance:

    mysql://myuser:password@localhost/myappdb

For most sites, this is the only file change that you absolutely have to make.

Install plugins
---------------

See additional information below for the nitty-gritty. The summary is that there
are at least a few plugins that will help you do what you need to (see the
[directory][pails-plugins]). To install a plugin, run the following command from
a command line at the root of your web site:

```sh
pails install plugin_name
```

Many (but not all) plugins have models that they save to the database you have
configured for your site. When you install a plugin, the sripts necessary to
update your database get placed in `db/migrations/`. To apply these changes,
run the following from a command prompt:

```sh
pails migrate
```

Add your own layout
-------------------

Pails ships with a decent look and feel, but it's probably not what you want to
stick with on your own web site. The default look and feel is defined in two
files, `views/_layout.php` and `css/custom.css`, but only the first is required
(the CSS file is simply included to provide some sane defaults). You can replace
the `_layout.php` with your own markup, as long as you include the following line
*somewhere* in the file (this causes pails to render the current page's content):

```php
<?php $this->render(); ?>
```

Two technical notes:
* You can override views provided by plugins. Simply create a file in your site's
  `views` directory with the same name as the view you'd like to override. For instance
  to override the form displayed by the `user/register.php` page provided by the
  pails-auth plugin, I would create a file at `views/user/register.php` with the
  desired content.
* You can define "partial views" (commonly called "partials") that get included into
  other views. The markup is the same as for a view, but including them is accompilshed
  by placing the following code in the view into which you want to include the partial:
  ```php
  <?php $this->render_partial('path/to/partial', $model) ?>
  ```
  The path is relative to the views directory. Omit the `.php`. The second argument is
  an optional model for the partial to use. If this argument is omitted, the current view's
  model is used.

Add your own functionality
--------------------------

pails is potentially infinitely extensible. The framework itself and all of the plugins
follow the MVC pattern, which means that the following rules always hold true (they are
the ONLY rules of pails, for the most part):

Models go in /models and extend ActiveRecord\Model. If you're using models, you'll
want the pails activerecord plugin:

```sh
pails install activerecord
```

Controllers go in /controllers with names like StuffController (case matters)
and extend Pails\Controller.

Views go in /views, in subfolders named by controller (all lowercase). Thus, a
view for the 'index' action of StuffController would be views/stuff/index.php.

Each *public* method in a Controller class is a valid action.

If you'd like to make some custom functionality available to the community, or
package it up for use in future projects, you can create a [plugin][pails-plugins].

Existing plugins
----------------

We've been using Pails over at [Synapse Software][synapse] for almost a year now,
so we have a wealth of additional functionality we're packaging up as plugins. You
too can build plugins and contribute them. Use this [test plugin][test_plugin] as
an example. If you want your plugin to be listed in the directory, submit a pull
request against the [pails-plugins][pails-plugins] repository.

Technical details
=================

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
    'require_admin' => array('except' => array('index')),
    'require_full_moon' => array('only' => array('werewolf', 'howl'))
);
```

BE WARNED that in a future release, the way to do this properly will be to fiddle
with these variables (or preferably to call a method by the same name, which doesn't
exist yet) inside the class's `__construct()` function. This eleminates the problem
caused by inheritance of subclasses clobbering before and after actions that are set
by superclasses.

Questions?
==========

Send email to bparks@synapsesoftware.com.

[blog]: http://bparks.github.io/
[gplv3]: http://www.gnu.org/licenses/gpl-3.0.html
[synapse]: http://synapsesoftware.com
[test_plugin]: https://github.com/bparks/pails-test-plugin
[pails-plugins]: https://github.com/bparks/pails-plugins
