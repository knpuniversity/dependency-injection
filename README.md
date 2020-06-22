Dependency Injection, Containers and Pimple
===========================================

Well hi there! Enclosed is the code that accompanies our tutorial on dependency
injection, DI contains, services and Pimple.

Installation
============

1) Download/install Composer into this directory. See http://getcomposer.org

2) Download the vendor files by running:

```
php composer.phar install
```

3) Make sure a few directories are writeable:

```
chmod -R 777 data
php data/setupDb.php
mkdir logs
chmod 777 logs
```

Run the App
-----------

To run the app, just execute the `app.php` file from the command line:

    php app.php

The application shouldn't have any output, but if you tail the log file,
you should see content added to it:

    tail -f logs/mail.log

Have fun!
