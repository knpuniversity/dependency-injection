Services and Dependency Injection
=================================

Our app is small now, but as it grows, the ``app.php`` file will get harder
and harder to read. The best way to fix this is to separate each different
chunk of functionality into different PHP classes and methods. Each of
these classes is called a "service" and the whole idea is sometimes called
`Service-Oriented Architecture`_.

Create a new file in ``src/DiDemo`` called ``FriendHarvester.php``, which
will be responsible for sending the email to every lucky person in the database. 
Add the namespace so that it follows the directory structure and give it an
``emailFriends`` method. Copy in all of our logic into this new method::

    // src/DiDemo/FriendHarvester.php
    namespace DiDemo;
    
    use DiDemo\Mailer\SmtpMailer;

    class FriendHarvester
    {
        public function send()
        {
            $mailer = new SmtpMailer(
                'smtp.SendMoneyToStrangers.com',
                'smtpuser',
                'smtppass',
                465
            );

            $sql = 'SELECT * FROM people_to_spam';
            foreach ($pdo->query($sql) as $row) {
                $mailer->sendMessage(
                    $row['email'],
                    'Yay! We want to send you money for no reason!',
                    sprintf(<<<EOF
    Hi %s! We've decided that we want to send you money for no reason!

    Please forward us all your personal information so we can make a deposit and don't ask any questions!
    EOF
                        , $row['name']),
                    'YourTrustedFriend@SendMoneyToStrangers.com'
                );
            }
        }
    }

.. note::

    To learn more about PHP namespaces, check out our free `PHP Namespaces in 120 Seconds`_
    tutorial.

.. note::

    The namespace follows the directory structure so the the class is automatically
    autoloaded by Composer's autoloader. For more on how this all works,
    see `Autoloading in PHP and the PSR-0 Standard`.

And just like that, you've created your first service! Roughly speaking,
a service is any PHP class that performs an action. Since this sends emails
to our new soon-to-be-rich friends, it's a service.

.. tip::

    An example of a PHP class that's *not* a service would be something that
    simply holds data, like a ``Blog`` class, with ``title``, ``author`` and
    ``body`` fields. These are sometimes called "Model objects".

The ``app.php`` code gets pretty simple now: just instantiate the ``FriendHarvester``
and call the method::

    // app.php
    use DiDemo\FriendHarvester;

    $friendHarvester = new FriendHarvester();
    $friendHarvester->send();

But when we try it, we get a huge error! Once we've
moved the code, we don't have access to the PDO object anymore. So how can
we get it?

Accessing External Objects from a Service
-----------------------------------------

This is our first important crossroads. There are a few cheating ways to
do this, like using the dreaded global keyword::

    public function send()
    {
        // Oh No!!!!!!
        global $pdo;
        
        // ...
        
        foreach ($pdo->query($sql) as $row) {
            // ...
        }
    }

Don't use this. You could also make the ``$pdo`` variable available statically,
by creating some class and then reference it::

    // app.php

    class Registry
    {
        static public $pdo;
    }
    Registry::$pdo = $pdo;
    
    $friendHarvester = new FriendHarvester();
    // ...

.. code-block:: php

    // src/DiDemo/FriendHarvester.php
    // ...

    public function send()
    {
        // Oh No Still!!!!!!
        $pdo = Registry::$pdo;
    
        // ...
    
        foreach ($pdo->query($sql) as $row) {
            // ...
        }
    }

The problem with both approaches is that our ``FriendHarvester`` has to assume
the ``$pdo`` variable has actually been set and is available. Or to say it
differently, when you use this class,  you need to make sure any global or
static variables it needs are setup. And the only way to know what the class
needs is to scan the file looking for global or static variable calls.
This makes ``FriendHarvester`` harder to understand and maintain, and much
harder to test.

Our Friend Dependency Injection
-------------------------------

Let's get rid of all of that and do this right. Since ``FriendHarvester``
needs the PDO object, add a ``__construct`` method with it as the first argument.
Set the value to a new private property and update our code to use it::

    // src/DiDemo/FriendHarvester.php
    // ...

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function send()
    {
        // ...

        foreach ($this->pdo->query($sql) as $row) {
            // ...
        }
    }

The ``FriendHarvester`` now makes a lot of sense: whoever instantiates it
*must* pass us a ``$pdo`` variable. Inside this class, we don't care *how*
this will happen, we just know that it will, and we can make use of it.

.. tip::

    You can also type-hint the argument, which is a great practice. We'll
    talk more about this later::

        public function __construct(\PDO $pdo)

This very simple idea is called `Dependency Injection`_, and you just nailed
it! Dependency injection means that if a class needs an object or some configuration,
we force that information to be passed into that class, instead of reaching
outside of it by using a global or static variable.

Back in ``app.php``, we now need to explicitly pass the PDO object when instantiating
the ``FriendHarvester``. Everything works exactly like before, except that
we've moved our logic into a service, which makes it testable, reusable,
and much more understandable for two reasons.

First, the class and method names (``FriendHarvester::emailFriends``) serve
as documentation for what our code does. Second, because we're using dependency
injection, it's clear what our service might do, because we can see what
outside things it needs.

.. _`Service-Oriented Architecture`: http://en.wikipedia.org/wiki/Service-oriented_architecture
.. _`PHP Namespaces in 120 Seconds`: http://knpuniversity.com/screencast/php-namespaces-in-120-seconds
.. _`Autoloading in PHP and the PSR-0 Standard`: http://phpmaster.com/autoloading-and-the-psr-0-standard/
.. _`Dependency Injection`: http://en.wikipedia.org/wiki/Dependency_injection