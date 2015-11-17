# Injecting Config & Services and using Interfaces

We've already created our first service *and* used dependency injection, we're
even closer to getting this money out! One problem with the `FriendHarvester`
is that we've hardcoded the SMTP configuration inside of it. What if we wanted
to re-use this class with a different configuration? Or what if our beta and
production setups use different SMTP servers? Right now, both are impossible!

## Injecting Configuration

When we realized that `FriendHarvester` needed the PDO object, we injected
it via the constructor. The same rule applies to configuration. Add a second
constructor argument, which will be an array of SMTP config and update the
code to use it:

```php
    // src/DiDemo/FriendHarvester.php

    private $pdo;

    private $smtpParams;

    public function __construct(\PDO $pdo, array $smtpParams)
    {
        $this->pdo = $pdo;
        $this->smtpParams = $smtpParams;
    }

    public function send()
    {
        $mailer = new \DiDemo\Mailer\SmtpMailer(
            $this->smtpParams['server'],
            $this->smtpParams['user'],
            $this->smtpParams['password'],
            $this->smtpParams['port']
        );
        
        // ...
    }
```

Back in `app.php`, pass the array when creating `FriendHarvester`:

```php
    // app.php
    
    $newsManager = new NewsletterManager($pdo, array(
        'server'    => 'smtp.SendMoneyToStrangers.com',
        'user'      => 'smtpuser',
        'password'  => 'smtppass',
        'port'      => 465
    ));
    $newsManager->send();
```

When we try it, it still works! Our class is more flexible now, but, let's level
up again!

## Injecting the Whole Mailer

We can now configure the `FriendHarvester` with different SMTP settings,
but what if we wanted to change how mails are sent entirely, like from SMTP
to sendmail? And what if we needed to use the mailer object somewhere else
in our app? Right now, we would need to create it anywhere we need it, since 
it's buried inside `FriendHarvester`.

In fact, `FriendHarvester` doesn't really care *how* we're sending emails,
it only cares that it has an `SmtpMailer` object so that it can call `sendMessage`. 
So like with the `PDO` object, it's a dependency. Refactor our class to pass
in the whole `SmtpMailer` object instead of just its configuration:

```php
    // src/DiDemo/FriendHarvester.php
    // ...

    private $pdo;

    private $mailer;

    public function __construct(\PDO $pdo, $mailer)
    {
        $this->pdo = $pdo;
        $this->mailer = $mailer;
    }

    public function send()
    {
        // ...
        foreach ($this->pdo->query($sql) as $row) {
            $this->mailer->sendMessage(
                // ...
            );
        }
    }
```

Update `app.php` to create the mailer object:

```php
    // app.php
    
    use DiDemo\Mailer\SmtpMailer;
    // ...

    $mailer = new SmtpMailer(
        'smtp.SendMoneyToStrangers.com',
        'smtpuser',
        'smtppass',
        465
    );

    $friendHarvester = new FriendHarvester($pdo, $mailer);
    $friendHarvester->send();
```

Try it out to make sure it still works. We would hate for our friends to miss
this opportunity!

Once again, this makes the `FriendHarvester` even more flexible and readable,
and will also make re-using the mailer possible. As a general rule, it's almost
always better to inject a service into another than to create it internally.
When you're in a service, think twice before using the `new` keyword, unless
you're instantiating a simple object that exists just to hold data as opposed
to doing some job (i.e. a "model object").

## Type-Hinting

One thing we've neglected to do is type-hint our two constructor arguments.
Let's do it now:

```php
    // serc/DiDemo/FriendHarvester.php
    use DiDemo\Mailer\SmtpMailer;
    // ...

    public function __construct(\PDO $pdo, SmtpMailer $mailer)
```

This is totally optional, but has a bunch of benefits. First, if you pass
something else in, you'll get a much clearer error message. Second, it
documents the class even further. A developer now knows exactly what methods
she can call on these objects. And third, if you use an IDE, this gives you
auto-completion! Type-hinting is optional, but I highly recommend it.

## Adding an Interface

Right now we're injecting an SMTPMailer. But in reality, `FriendHarvester`
only cares that the mailer has a `sendMessage` method on it. But even if we 
had another class with an identical method, like `SendMailMailer` for example, 
we couldn't use it because of the specific type-hint.

To make this more awesome, create a new `MailerInterface.php` file, which holds
an interface with the single send method that all mailers must have:

```php
    // src/DiDemo/Mailer/MailerInterface.php
    namespace DiDemo\Mailer;

    interface MailerInterface
    {
        public function sendMessage($recipientEmail, $subject, $message, $from);
    }
```

Update `SmtpMailer` to implement the interface and change the type-hint
in `FriendHarvester` as well:

```php
    // src/DiDemo/Mailer/SmtpMailer.php
    // ...
    
    class SmtpMailer implements MailerInterface
```

```php
    // src/DiDemo/FriendHarvester.php
    
    use DiDemo\Mailer\MailerInterface;
    // ...
    
    public function __construct(\PDO $pdo, MailerInterface $mailer)
```

When you're finished, try the application again - everything should still work
just fine. And with any luck you will find a place for all of that annoying money.

Just like with every step so far, this has a few great advantages. First,
`FriendHarvester` is more flexible since it now accepts any object that
implements `MailerInterface`. Second, it documents our code a bit more.
It's clear now exactly what small functionality `FriendHarvester` actually
needs. Finally, in `SmtpMailer`, the fact that it implements an interface
with a `sendMessage` method tells us that this method is particularly important.
The class could have other methods, but `sendMessage` is probably an especially
important one to focus on.
