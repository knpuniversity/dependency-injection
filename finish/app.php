<?php

require __DIR__.'/vendor/autoload.php';

/* START CONTAINER BUILDING */

$container = new Pimple();

require __DIR__.'/app/config.php';
require __DIR__.'/app/services.php';

/* END CONTAINER BUILDING */

/** @var $friendHarvester \DiDemo\FriendHarvester */
$friendHarvester = $container['friend_harvester'];
$friendHarvester->emailFriends();
