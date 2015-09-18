Pails
=====

Pails is a seriously lightweight MVC framework written in PHP. The overarching
vision is that less code is more powerful (and certainly gives rise to fewer
bugs). [Read more on my blog][blog]

Pails is licensed under the [GPLv3][gplv3]. If the GPL doesn't work for you,
let's talk about your use case. If you need verification that this is free
software (for your boss, CTO, etc.), that can also be arranged.

Come talk about pails in #pails on Freenode.

Upgrading a pails app from 0.3.x
--------------------------------

The organization of directories has changed a little bit, so that application
logic goes in `app` and public files go in `public`. The quick solution is to
run the following three commands in the root of your pails app:

```sh
mkdir app public
mv config controllers helpers initializers models views app/
mv css js images uploads robots.txt .htaccess public
```

Then you can update the pails composer package and your app will run *mostly*
fine. Due to the change in paths, logic that expects to write to a web-accessible
location should be verified/tested.

Quick start
-----------

1. Install pails from composer
2. Set up your directories
3. Configure your database
4. Install plugins/packages
5. Add your own layout
6. (Optional) Add your own functionality, using the MVC pattern

Note that there are a set of tools at https://github.com/bparks/pails-tools
that will do step 2 (and set up some sample infrastructure) for you, so all
you have to do is jump into the directory, run `composer install` and be up
and running.

Install pails
-------------

Create a new directory for your project and run

```sh
composer require pails
```

Set up your directories
-----------------------

`pails-tools` will do this for you, but the typical pails app has the following
structure:

```text
my_app
|- app
|  |- config
|  |  |- application.php (database config)
|  |- initializers
|  |  |- _startup.php (initialize any packages)
|  |- controllers
|  |- models
|  |- views
|- public (this is your document root)
|- vendor
|- db (this contains migrations)
|- scripts (any scripts that need the app's environment for e.g. cron)
|- composer.json
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

Pails uses composer. All packages will be autoloaded according to the autoload
rules they specify in their `composer.json` files AUTOMATICALLY.

Add your own layout
-------------------

Pails ships with a decent look and feel, but it's probably not what you want to
stick with on your own web site. The default look and feel is defined in two
files, `app/views/_layout.php` and `public/css/custom.css`, but only the first is required
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
  pails-auth plugin, I would create a file at `app/views/user/register.php` with the
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

Models go in /models and tend to extend \ActiveRecord\Model. It's not required, but pails
works really well with PHP-ActiveRecord, which you can get through composer:

```sh
composer require php-activerecord
```

Controllers go in /controllers with names like StuffController (case matters)
and extend \Pails\Controller.

Views go in /views, in subfolders named by controller (all lowercase). Thus, a
view for the 'index' action of StuffController would be views/stuff/index.php.

Each *public* method in a Controller class is a valid action.

Plugins
-------

Initially, pails had it its own plugin system, but composer has largely supplanted
that. The trend of how to use a composer package with pails is:

1. Install the package from composer
2. If required, initialize the package in `initializers/_startup.php` or your own
   initializer (initializers are evaluated alphabetically; _startup MUST be first).
3. Use the `use` keyword in application logic to include functionality, just like in
   any other PHP app.

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

Send email to bparks@brianparks.me.

[blog]: http://brianparks.me/blog/
[gplv3]: http://www.gnu.org/licenses/gpl-3.0.html
