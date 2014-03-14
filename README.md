Pails
=====

Pails is a seriously lightweight MVC framework written in PHP. The overarching
vision is that less code is more powerful (and certainly gives rise to fewer
bugs).

For now, here's how to use pails:

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

    cp lib/pails/example/* .

Verify that .htaccess and config/application.php.example exist

Clone php-activerecord into the lib directory
---------------------------------------------

pails uses php-activerecord for persistence. This makes data access trivial.

Configure your app
------------------

Move config/application.php.example to config/application.php and change
values as necessary.

Build an app!
-------------

Models go in /models and extend ActiveRecord\Model.

Controllers go in /controllers with names like StuffController (case matters)
and extend Pails\Controller.

Views go in /views, in subfolders named by controller (all lowercase). Thus, a
view for the 'index' action of StuffController would be views/stuff/index.php.

Each *public* method in a Controller class is a valid action.

Questions?
----------

Send email to bparks@synapsesoftware.com.
