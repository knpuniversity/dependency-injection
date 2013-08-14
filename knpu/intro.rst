Dependency Injection
====================

Hi guys! In this tutorial, we're going to talk about dependency injection,
services, and dependency injection containers by looking at a simple one
called Pimple. The great news is that understanding these things isn't hard,
but it can dramatically increase the quality and maintainability of the
code you write.

As always, we'll be coding with a real example. Recently, we noticed that a lot
of really nice rich people have been emailing us trying to give away their
money. In this tutorial, we're going to create a simple app to help these
fine people, we're calling it SendMoneyToStrangers.com.

I've already bootstrapped a small app, which you can download. It uses an
Sqlite database, so make sure you have it installed, then ``chmod 777`` the
data directory and run a script that creates some dummy data for us:

.. code-block:: bash

    chmod -R 777 data/
    php data/setupDb.php

The app is really simple: it queries the database, then delivers emails to
each person using some SmtpMailer class. You could use any mailer library
here, and I've made this class fake the sending of emails for simplicity. 
Instead, it just logs details to a file.

.. note::

    We're using `Composer for autoloading`_ files in our ``src/`` directory
    with the following ``composer.json``:

    .. code-block:: json
    
        {
            "autoload": {
                "psr-0": {"DiDemo": "src/"}
            }
        }

Tail the log file then run the app via ``php app.php`` from the command line.
You'll see two emails are sent to two lucky people.

.. _`Composer for autoloading`: http://getcomposer.org/doc/01-basic-usage.md#autoloading