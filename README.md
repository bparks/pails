Pails
=====

Pails is a seriously lightweight MVC framework written in PHP. The overarching
vision is that less code is more powerful (and certainly gives rise to fewer
bugs). [Read more on my blog][blog]

Pails is licensed under the [GPLv3][gplv3]. If the GPL doesn't work for you,
let's talk about your use case. If you need verification that this is free
software (for your boss, CTO, etc.), that can also be arranged.

Come talk about pails in #pails on Freenode.

Sites using pails
-----------------

* [#dreammaker][dreammaker] - A platform for people to make dreams and others
  to help them come true
* [Front Range Barbeque][frbbq] - The version currently under development uses pails
* [MailOver][mailover] - Send mail to your friends via twitter! (Only the dev
  site uses pails)

Building a site with pails
--------------------------

You can build a pails app using some automated scripts OR manually. Using scripts
is probably faster.

First, install pails:

    git clone https://github.com/bparks/pails.git
    cd pails && make install              # Note: if you don't have permissions to
                                          # /usr/local/**/, you'll need to precede
                                          # the installation command with 'sudo'

Then, run the pails command to build out a new tree for you:

    pails new my_pails_app    # Creates a new app
    cd my_pails_app
    pails server    # Runs the PHP development server (requires PHP 5.4+)

TODOs
-----

* Build an example app
* Make a shiny new pails app do more than complain about missing pieces

Build an app!
-------------

Models go in /models and extend ActiveRecord\Model.

Controllers go in /controllers with names like StuffController (case matters)
and extend Pails\Controller.

Views go in /views, in subfolders named by controller (all lowercase). Thus, a
view for the 'index' action of StuffController would be views/stuff/index.php.

Each *public* method in a Controller class is a valid action.

Before and after actions
------------------------

Just like Rails, pails has before and after actions. Right now, they need to be
public methods, which are configured as before or after actions with a class-level
variable called `$before_actions` or `$after_actions`, like so:

    $before_actions = array('require_login', 'require_admin');

The referenced functions can't take any arguments.

You can also exclude them from beign applicable to certain actions by making this
array associative,  like so:

    $before_actions = array(
        'require_login',
        'require_admin' => array('except' => array('index'))
    );

BE WARNED that in a future release, the way to do this properly will be to fiddle
with these variables (or preferably to call a method by the same name, which doesn't
exist yet) inside the class's `__construct()` function. This eleminates the problem
caused by inheritance of subclasses clobbering before and after actions that are set
by superclasses.

Questions?
----------

Send email to bparks@synapsesoftware.com.

The manual way (if you really want it)
======================================

Construct a webroot
-------------------

    webroot
    |-lib
    |-controllers
    |-models
    |-views
    |-config
    |-[images]
    |-[css]
    |-[js]
    |-<etc. - however you normally structure your webroots>

Clone the pails repository
--------------------------

If you're using git for your project, clone pails as a *submodule*, executing
the following commands from your webroot:

    git submodule init
    git submodule add https://github.com/bparks/pails.git lib/pails

Otherwise, just clone the repo into [webroot]/lib/pails

Copy the necessary config files where they need to go
-----------------------------------------------------

From the webroot:

    cp -r lib/pails/example/* .

Verify that .htaccess and config/application.php.default exist

Clone php-activerecord into the lib directory
---------------------------------------------

pails uses php-activerecord for persistence. This makes data access trivial.

    git clone https://github.com/jpfuentes2/php-activerecord.git lib/php-activerecord

Configure your app
------------------

Move config/application.php.example to config/application.php and change
values as necessary.


[blog]: http://bparks.github.io/
[gplv3]: http://www.gnu.org/licenses/gpl-3.0.html
[dreammaker]: http://dreams.thoughtcolony.com/
[frbbq]: http://frbbq.com/
[mailover]: http://mailover.synapsesoftware.com/
