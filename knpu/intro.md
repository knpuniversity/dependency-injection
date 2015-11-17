# Dependency Injection

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
Sqlite database, so make sure you have it installed, then `chmod 777` the
data directory and run a script that creates some dummy data for us:

```bash
chmod -R 777 data/
php data/setupDb.php
```

The app is really simple:

[[[ code('9376f72c2a') ]]]

It queries the database, then delivers emails to each person using some
`SmtpMailer` class:

[[[ code('304ed366c5') ]]]

You could use any mailer library here, and I've made this class fake the
sending of emails for simplicity. Instead, it just logs details to a file:

[[[ code('fe92cfe82b') ]]]

***TIP
We're using [Composer for autoloading][1] files in our `src/` directory
with the following `composer.json`:

[[[ code('bcfc0e8ea4') ]]]
***

Tail the log file:

```bash
tail -f logs/mail.log
```

Then run the app via `php app.php` from the command line:

```bash
php app.php
```

You'll see two emails are sent to two lucky people.


[1]: knpuniversity.com/composer
