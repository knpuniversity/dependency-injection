# Injecting Config & Services and using Interfaces

We've already created our first service *and* used dependency injection, we're
even closer to getting this money out! One problem with the `FriendHarvester`
is that we've hardcoded the SMTP configuration inside of it:

[[[ code('7a4e4596ba') ]]]

What if we want to re-use this class with a different configuration? Or what
if our beta and production setups use different SMTP servers? Right now, both
are impossible!

## Injecting Configuration

When we realized that `FriendHarvester` needed the PDO object, we injected
it via the constructor. The same rule applies to configuration. Add a second
constructor argument, which will be an array of SMTP config and update the
code to use it:

[[[ code('962b7dfccd') ]]]

Back in `app.php`, pass the array when creating `FriendHarvester`:

[[[ code('2e88521e4e') ]]]

When we try it:

```bash
php app.php
```

It still works! Our class is more flexible now, but, let's level up again!

## Injecting the Whole Mailer

We can now configure the `FriendHarvester` with different SMTP settings,
but what if we wanted to change how mails are sent entirely, like from SMTP
to sendmail? And what if we needed to use the mailer object somewhere else
in our app? Right now, we would need to create it anywhere we need it, since 
it's buried inside `FriendHarvester`.

In fact, `FriendHarvester` doesn't really care *how* we're sending emails,
it only cares that it has an `SmtpMailer` object so that it can call `sendMessage()`. 
So like with the `PDO` object, it's a dependency. Refactor our class to pass
in the whole `SmtpMailer` object instead of just its configuration:

[[[ code('5c74100b5c') ]]]

Update `app.php` to create the mailer object:

[[[ code('083f3f5e83') ]]]

Try it out to make sure it still works:

```bash
php app.php
```

We would hate for our friends to miss this opportunity!

Once again, this makes the `FriendHarvester` even more flexible and readable,
and will also make re-using the mailer possible. As a general rule, it's almost
always better to inject a service into another than to create it internally.
When you're in a service, think twice before using the `new` keyword, unless
you're instantiating a simple object that exists just to hold data as opposed
to doing some job (i.e. a "model object").

## Type-Hinting

One thing we've neglected to do is type-hint our two constructor arguments.
Let's do it now:

[[[ code('ecd1bb207e') ]]]

This is totally optional, but has a bunch of benefits. First, if you pass
something else in, you'll get a much clearer error message. Second, it
documents the class even further. A developer now knows exactly what methods
she can call on these objects. And third, if you use an IDE, this gives you
auto-completion! Type-hinting is optional, but I highly recommend it.

## Adding an Interface

Right now we're injecting an `SmtpMailer`. But in reality, `FriendHarvester`
only cares that the mailer has a `sendMessage()` method on it. But even if we 
had another class with an identical method, like `SendMailMailer`, for example, 
we couldn't use it because of the specific type-hint.

To make this more awesome, create a new `MailerInterface.php` file, which holds
an interface with the single send method that all mailers must have:

[[[ code('76c4fbea25') ]]]

Update `SmtpMailer` to implement the interface and change the type-hint
in `FriendHarvester` as well:

[[[ code('2771a99a50') ]]]

[[[ code('bd35fecb8a') ]]]

When you're finished, try the application again:

```bash
php app.php
```

Everything should still work just fine. And with any luck you will find a place
for all of that annoying money.

Just like with every step so far, this has a few great advantages. First,
`FriendHarvester` is more flexible since it now accepts any object that
implements `MailerInterface`. Second, it documents our code a bit more.
It's clear now exactly what small functionality `FriendHarvester` actually
needs. Finally, in `SmtpMailer`, the fact that it implements an interface
with a `sendMessage()` method tells us that this method is particularly
important. The class could have other methods, but `sendMessage()` is
probably an especially important one to focus on.
