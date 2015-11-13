<?php

require __DIR__.'/vendor/autoload.php';

use DiDemo\Mailer\SmtpMailer;

$dsn = 'sqlite:'.__DIR__.'/data/database.sqlite';
$pdo = new PDO($dsn);

$mailer = new SmtpMailer('smtp.SendMoneyToStrangers.com', 'smtpuser', 'smtppass', '465');

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