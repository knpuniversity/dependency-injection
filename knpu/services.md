# Services and Dependency Injection

Our app is small now, but as it grows, the `app.php` file will get harder
and harder to read. The best way to fix this is to separate each different
chunk of functionality into different PHP classes and methods. Each of
these classes is called a "service" and the whole idea is sometimes called
[Service-Oriented Architecture][1].

Create a new file in `src/DiDemo` called `FriendHarvester.php`, which will
be responsible for sending the email to every lucky person in the database:

[[[ code('56c9f24c3a') ]]]

Add the namespace so that it follows the directory structure and give it an
`emailFriends` method:

[[[ code('065f5da123') ]]]

Copy in all of our logic into this new method:

[[[ code('413dbb5d08') ]]]

***SEEALSO
To learn more about PHP namespaces, check out our free [PHP Namespaces in 120 Seconds][2]
tutorial
***

***TIP
The namespace follows the directory structure so the the class is automatically
autoloaded by Composer's autoloader. For more on how this all works,
see [Autoloading in PHP and the PSR-0 Standard][3].
***

And just like that, you've created your first service! Roughly speaking,
a service is any PHP class that performs an action. Since this sends emails
to our new soon-to-be-rich friends, it's a service.

***TIP
An example of a PHP class that's *not* a service would be something that
simply holds data, like a `Blog` class, with `title`, `author` and
`body` fields. These are sometimes called "Model objects".
***

The `app.php` code gets pretty simple now: just instantiate the `FriendHarvester`
and call the method:

[[[ code('a64c51975a') ]]]

But when we try it:

```bash
php app.php
```

We get a huge error!

Once we've moved the code, we don't have access to the PDO object anymore.
So how can we get it?

## Accessing External Objects from a Service

This is our first important crossroads. There are a few cheating ways to
do this, like using the dreaded global keyword:

[[[ code('3bbbcd8475') ]]]

Don't use this. You could also make the `$pdo` variable available statically,
by creating some class and then reference it:

[[[ code('62a62ec64e') ]]]

[[[ code('1d40bd0eea') ]]]

The problem with both approaches is that our `FriendHarvester` has to assume
the `$pdo` variable has actually been set and is available. Or to say it
differently, when you use this class,  you need to make sure any global or
static variables it needs are setup. And the only way to know what the class
needs is to scan the file looking for global or static variable calls.
This makes `FriendHarvester` harder to understand and maintain, and much
harder to test.

## Our Friend Dependency Injection

Let's get rid of all of that and do this right.

Since `FriendHarvester` needs the PDO object, add a `__construct()` method
with it as the first argument. Set the value to a new private property and
update our code to use it:

[[[ code('da98d4ce0e') ]]]

The `FriendHarvester` now makes a lot of sense: whoever instantiates it
*must* pass us a `$pdo` variable. Inside this class, we don't care *how*
this will happen, we just know that it will, and we can make use of it.

***TIP
You can also type-hint the argument, which is a great practice. We'll
talk more about this later:

```php
public function __construct(\PDO $pdo)
```
***

This very simple idea is called [Dependency Injection][4], and you just nailed
it! Dependency injection means that if a class needs an object or some configuration,
we force that information to be passed into that class, instead of reaching
outside of it by using a global or static variable.

Back in `app.php`, we now need to explicitly pass the PDO object when instantiating
the `FriendHarvester`:

[[[ code('af656e1f17') ]]]

Run it:

```bash
php app.php
```

Everything works exactly like before, except that we've moved our logic into
a service, which makes it testable, reusable, and much more understandable
for two reasons.

First, the class and method names (`FriendHarvester::emailFriends()`) serve
as documentation for what our code does. Second, because we're using dependency
injection, it's clear what our service might do, because we can see what
outside things it needs.


[1]: http://en.wikipedia.org/wiki/Service-oriented_architecture
[2]: http://knpuniversity.com/screencast/php-namespaces-in-120-seconds
[3]: http://phpmaster.com/autoloading-and-the-psr-0-standard/
[4]: http://en.wikipedia.org/wiki/Dependency_injection
