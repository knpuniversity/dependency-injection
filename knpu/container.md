# Dependency Injection Container

Our project now has services, an interface, and is fully using dependency
injection. Nice work! One of the downsides of DI is that all the complexity
of creating and configuring objects is now your job. This isn't so bad since
it all happens in one place and gives you so much control, but it is something
we can improve!

If you want to make this easier, the tool you need is called a dependency injection
container. A lot of DI containers exist in PHP, but let's use Composer to grab the
simplest one of all, called [Pimple][1]. Add a `require` key to `composer.json` to
include the library:

```json
    {
        "autoload": {
            "psr-0": {"DiDemo": "src/"}
        },
        "require": {
            "pimple/pimple": "1.0.*"
        }
    }
```

Make sure you've [downloaded Composer][2], and then run `php composer.phar install`
to download Pimple.

> If you're new to Composer, check out our free [The Wonderful World of Composer Tutorial][3].

Pimple is both powerful, and tiny. Kind of like having one on prom night. 
It is just a single file taking up around 200 lines. That's one reason I love it!

Create a new Pimple container. This is an object of course, but it looks
and acts like an array that we store all of our service objects on:

```php
    // app.php
    // ...

    $container = new Pimple();
```

Start by adding the `SmtpMailer` object under a key called `mailer`. Instead
of setting it directly, wrap it in a call to `share` and in an anonymous
function. We'll talk more about this in a second, but just return the mailer
object from the function for now:

```php
    // app.php
    // ...

    $container['mailer'] = $container->share(function() {
        return new SmtpMailer(
            'smtp.SendMoneyToStrangers.com',
            'smtpuser',
            'smtppass',
            465
        );
    });
```

To access the `SmtpMailer` object, use the array syntax again:

```php
    // app.php
    // ...

    $newsManager = new NewsletterManager($pdo, $container['mailer']);
```

It's that simple! Run the application to spam...I mean send great opportunities
to our friends!

## Shared and Lazy Services

We haven't fully seen the awesomeness of the container yet, but there are
already some cool things happening. First, wrapping the instantiation of
the `mailer` service in an anonymous function makes its creation "lazy".
This means that the object isn't created until much later when we reference
the `mailer` service and ask the container to give it to us. And if we
never reference `mailer`, it's never created at all - saving us time and
memory.

Second, using the `share` method means that no matter how many times we
ask for the `mailer` service, it only creates it once. Each call returns 
the original object:

```php
    $mailer1 = $container['mailer'];
    $mailer2 = $container['mailer'];

    // there is only 1 mailer, the 2 variables hold the same one
    $willBeTrue = $mailer1 === $mailer2;
```

This is a very common property of a service: you only ever need just one.
If we need to send many emails, we don't need many mailers, we just need
the one and then we'll call `send` on it many times. This also makes our code
faster and less memory intensive, since the container guarantees that we
only have one mailer. This is another detail that we don't need to worry
about.

# Now witness the Geek-Awesomeness of this fully armed and operational Container!

Let's keep going and add our other services to the container. But first,
I'll add some comments to separate which part of our code is building the
container, and which part is our actual application code::

```php
    /* Start Container Building */
    $container = new Pimple();
    
    // ...
    
    /* End Container Building */
    
    $friendHarvester = new FriendHarvester();
    $friendHarvester->emailFriends();
```

Let's add `FriendHarvester` to the container next:

```php
    $container['friend_harvester'] = $container->share(function() {
        return new FriendHarvester($pdo, $container['mailer']);
    });
```

That's easy, except that we somehow need access to the `PDO` object and
the container itself so we can get two required dependencies. Fortunately,
the anonymous function is passed an argument, which is the Pimple container
itself:

```php
    $container['friend_harvester'] = $container->share(function(Pimple $container) {
        return new FriendHarvester($pdo, $container['mailer']);
    });
```

To fix the missing `PDO` object, just make it a service as well:

```php
    $container['pdo'] = $container->share(function() {
        $dsn = 'sqlite:'.__DIR__.'/data/database.sqlite';

        return new PDO($dsn);
    });
```

Now we can easily update the `friend_harvester` service configuration to
use it:

```php
    $container['friend_harvester'] = $container->share(function(Pimple $container) {
        return new FriendHarvester($container['pdo'], $container['mailer']);
    });
```

With the new `friend_harvester` service, update the application code to
just grab it out of the container:

```php
    $friendHarvester = $container['friend_harvester'];
    $friendHarvester->send();
```

Now that all three of our services are in the container, you can start to
see the power that this gives us. All of the logic of exactly which objects
depend on which other object is abstracted away into the container itself.
Whenever we need to use a service, we just reference it: we don't care how
it's created or what dependencies *it* may have, it's all handled elsewhere.
And if the constructor arguments for a service like the `mailer` change later,
we only need to update one spot in our code. Nobody else knows or cares about
this change.

Remember also that the services are constructed lazily. When we ask for the
`friend_harvester`, the `pdo` and `mailer` services haven't been instantiated
yet. Fortunately, the container is smart enough to create them first, and
then pass them into the `FriendHarvester` constructor. All of that happens
automatically, behind the scenes.

## Configuration

But a container can hold more than just services, it can house our configuration
as well. Create a new key on the container called `database.dsn`, set it to
our configuration, and then use it when we're creating the PDO object::

```php
    $container['database_dsn'] = 'sqlite:'.__DIR__.'/data/database.sqlite';

    $container['pdo'] = $container->share(function(Pimple $container) {
        return new PDO($container['database_dsn']);
    });
```

We're not using the `share` method or the anonymous function because this
is just a scalar value, and we don't need to worry about that lazy-loading
stuff.

We can do the same thing with the SMTP configuration parameters. Notice that
the name I'm giving to each of these parameters isn't important at all, I'm
just inventing a sane pattern and using the name where I need it::

```php
    $container['mailer.server'] = 'smtp.SendMoneyToStrangers.com';
    $container['mailer.user'] = 'smtpuser';
    $container['mailer.pass'] = 'smtppass';
    $container['mailer.port'] = 465;
    
    // ...
    $container['mailer'] = $container->share(function(Pimple $container) {
         return new SmtpMailer(
            $container['mailer.server'],
            $container['mailer.user'],
            $container['mailer.pass'],
            $container['mailer.port']
         );
     });
```

When we're all done, the application works exactly as before. What we've
gained is the ability to keep all our configuration together. This would
make it very easy to change our database to use MySQL or change the
SMTP password.

## Move Configuration into a Separate File

Now that we have this flexibility, let's move the configuration and service
building into separate files altogether. Create a new `app/` directory and 
`config.php` and `services.php` files. Require each of these from the `app.php` 
script right after creating the container::

```php
    $container = new Pimple();
    require __DIR__.'/app/config.php';
    require __DIR__.'/app/services.php';
```

Next, move the configuration logic into `config.php` and all the services into 
`services.php`. Be sure to update the Sqlite database path in `config.php`
since we just moved this file::

```php
    // app/config.php
    
    $container['database_dsn'] = 'sqlite:'.__DIR__.'/../data/database.sqlite';
    $container['mailer.server'] = 'smtp.SendMoneyToStrangers.com';
    // ...
```

```php
    // app/services.php
    use DiDemo\FriendHarvester;
    use DiDemo\Mailer\SmtpMailer;

    $container['pdo'] = $container->share(function(Pimple $container) {
        return new PDO($container['database_dsn']);
    });

    // ...
```

## Skinny Controllers and Service-Oriented Architecture

Awesome! We now have configuration, service-building and our actual application
code all separated into different files. Notice how clear our actual app
code is now - it's just one line to get out a service and another to use
it.

If this were a web application, this would live in a controller. You'll
often hear that you should have "skinny controllers" and a "fat model". And
whether you realize it or not, we've just seen that in practice! When we
started, `app.php` held all of our logic. After refactoring into services
and using a service container, `app.php` is skinny. The "fat model" refers
to moving all of your logic into separate, single-purpose classes, which
are sometimes referred to collectively as "the model". Another term for this
is service-oriented architecture.

In the real world, you may not always have skinny controllers, but always
keep this philosophy in your mind. The skinnier your controllers, the more
readable, reusable, testable and maintainable that code will be. What's better,
a 300 line long chunk of code or 5 lines that use a few well-named and small
service objects 

> Or an awesome email about collecting millions of dollars?!

## Auto-completion with a Container

One of the downsides to using a container is that your IDE and other developers
don't exactly know what type of object a service may be. There's no perfect
answer to this, since a container is very dynamic by nature. But what you
*can* do is use PHP documentation whenever possible to explicitly say what
type of object something is.

For example, after fetching the `friend_harvester` service, you can use
a single-line comment to tell your IDE and other developers exactly what
type of object we're getting back::

```php
    /** @var \DiDemo\FriendHarvester $friendHarvester */
    $friendHarvester = $container['friend_harvester'];
```

This gives us IDE auto-complete on the `$friendHarvester` variable. 
Another common tactic is to create an object or sub-class the container
and add specific methods that return different services and have proper PHP
doc on them. I won't show it here, but imagine we've sub-classed
the `Pimple` class and added a `getFriendHarvester` method which has
a proper `@return` PHP doc on it.


[1]: http://pimple.sensiolabs.org/
[2]: http://getcomposer.org/download/
[3]: http://knpuniversity.com/screencast/composer
