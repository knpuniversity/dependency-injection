# 02-services

## Challenge 1

---> this is done, RefactorOutGlobalCoding

## Challenge 2

---> this is done, WhatsWrongWithGlobalMC

You fixed Bob's code to not use globals anymore.
"But wait!" - he says - "What's so wrong with using
globals? My code was a lot easier before!".

What's the best answer to give Bob:

A) Globals are deprecated and are eventually going to be removed from PHP.

B) Globals make your code difficult to debug and read. When you have `global $emailLoader`, I wonder - who set this variable? And where? What type of object is this?

C) Globals are not as performant as using dependency injection.

D) The `$GLOBALS` variable is available when using a web server, but *not* when running command-line tasks. This makes your code very breakable if you want to use the same code to do batch processing jobs.

**EXPLANATION**

The only true statement is (B): globals makes your code difficult
to debug, understand and maintain. When using `global $emailLoader`,
it's not clear where this is created or who created it. And what's
worse, in a different file, you may innocently rename or remove
`$emailLoader` not realizing that many other parts of your code
are relying on this being available globally!

Writing code where you pass in needed values (dependency injection)
takes more work. The pay-off are classes that are easier to read
and a lot more predictable (and more unit-testable, etc).

# 03-injection-interfaces

## Challenge 1

Ok, it's time to get organized! You've already installed the
`pimple/pimple` library *and* created your `$container` variable.
Now, add two services to it:

* `email_loader` - for the `EmailAddressLoader` object
* `our_happy_sender` - for the `HappyMessageSender`.

Then, simplify your code! At the bottom, get the `our_happy_sender`
service out of the container instead of creating it manually.

***STARTING FILES
```sendHappy.php
<?php
require __DIR__.'/vendor/autoload.php';

$container = new Pimple();

// configure your container here

\$emailLoader = new EmailAddressLoader();
\$happyMessageSender = new HappyMessageSender(\$emailLoader);

\$happyMessageSender->sendHappiness();
```

```EmailAddressLoader.php
Same as at the end of RefactorOutGlobalCoding
```

```HappyMessageSender.php
Same as at the end of RefactorOutGlobalCoding
```

## Challenge 2

Question: Check out the following code:

```php
$container = new Pimple();
$container['email_loader'] = $container->share(function() {
    return EmailAddressLoader();
});

$loader1 = $container['email_loader'];
$loader2 = $container['email_loader'];
```

Which of the following is most accurate:

A) The `$loader1` and `$loader2` objects are the exact same object in memory
B) The container creates 2 new `EmailAddressLoader` objects: one each time we ask for the `email_loader` service.
C) The container creates *one* `email_loader` object when the container is loaded, then returns just that one object each time we ask for the `email_loader` service.
D) The container creates *zero* `EmailAddressLoader` objects: it doesn't create them until you actually call a method on the object.

Correct: A

Explanation:

A container gives us two awesome things:

1. When you ask for `email_loader`, it is only ever created once
2. The `EmailAddressLoader` is *not* created until (and unless) we ask for the `email_loader` service.

## Challenge 3

Questions:

Ah, new requirements! The `EmailAddressLoader` just returns
some hardcoded emails. That's ok for now. But eventually, you're
going to create a `DatabaseEmailAddressLoader` that will pull
the emails from the database.

In preparation for this, you've created a new interface called
`EmailLoaderInterface`. Update `EmailLoader` to implement this
and then change the type-hint in `HappyMessageSender` to allow
*any* object that implements this interface.

***STARTING CODE

```sendHappy.php
Use the ending code from Challenge 1
```

```EmailAddressLoader.php
Same as at the end of RefactorOutGlobalCoding
```

```HappyMessageSender.php
Same as at the end of RefactorOutGlobalCoding
```

```EmailLoaderInterface.php
<?php

interface EmailLoaderInterface
{
    public function getAllEmails();
}
```
