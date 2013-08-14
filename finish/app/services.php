<?php

use DiDemo\Mailer\SmtpMailer;
use DiDemo\FriendHarvester;

$container['mailer'] = $container->share(function(Pimple $container) {
    return new SmtpMailer(
        $container['smtp.server'],
        $container['smtp.user'],
        $container['smtp.password'],
        $container['smtp.port']
    );
});

$container['friend_harvester'] = $container->share(function(Pimple $container) {
    return new FriendHarvester($container['pdo'], $container['mailer']);
});

$container['pdo'] = $container->share(function(Pimple $container) {

    return new PDO($container['database.dsn']);
});