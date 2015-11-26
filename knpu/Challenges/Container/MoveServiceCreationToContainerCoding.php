<?php

namespace Challenges\Container;

use KnpU\Gladiator\CodingChallenge\ChallengeBuilder;
use KnpU\Gladiator\CodingChallenge\CodingContext;
use KnpU\Gladiator\CodingChallenge\CodingExecutionResult;
use KnpU\Gladiator\CodingChallenge\CorrectAnswer;
use KnpU\Gladiator\CodingChallenge\Exception\GradingException;
use KnpU\Gladiator\CodingChallenge\File;
use KnpU\Gladiator\CodingChallengeInterface;
use KnpU\Gladiator\Grading\HtmlOutputGradingTool;
use KnpU\Gladiator\Grading\PhpGradingTool;
use KnpU\Gladiator\Worker\WorkerLoaderInterface;

class MoveServiceCreationToContainerCoding implements CodingChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
Ok, it's time to get organized! You've already installed the
`pimple/pimple` library *and* created your `\$container` variable.
Now, add two services to it:

* `email_loader` - for the `EmailAddressLoader` object;
* `happy_sender` - for the `HappyMessageSender` object.

Then, simplify your code! At the bottom, get the `happy_sender`
service out of the container instead of creating it manually.
EOF;
    }

    public function getChallengeBuilder()
    {
        $builder = new ChallengeBuilder();
        $builder
            ->addFileContents('sendHappy.php', <<<EOF
<?php

require __DIR__.'/vendor/autoload.php';

\$container = new Pimple();

// configure your container here

\$emailLoader = new EmailAddressLoader();
\$happyMessageSender = new HappyMessageSender(\$emailLoader);
\$happyMessageSender->sendHappiness();
EOF
            )
            ->addFileContents('EmailAddressLoader.php', <<<EOF
<?php

class EmailAddressLoader implements EmailLoaderInterface
{
    public function getAllEmails()
    {
        // a class to fake loading emails (e.g. from a database)
        return array(
            'iluvhappyemails@symfony.com',
            'grumpy_dude@yahoo.com',
            'sunshine_gurl@gmail.com',
        );
    }
}
EOF
            , File::MODE_READONLY_ENABLED)
            ->addFileContents('HappyMessageSender.php', <<<EOF
<?php

class HappyMessageSender
{
    private \$emailLoader;

    public function __construct(EmailLoaderInterface \$emailLoader)
    {
        \$this->emailLoader = \$emailLoader;
    }

    public function sendHappiness()
    {
        \$emails = \$this->emailLoader->getAllEmails();
        foreach (\$emails as \$email) {
            // just print for testing
            echo 'I hope you\'re having a GREAT day '.\$email.'<hr/>';
        }
    }
}
EOF
            , File::MODE_READONLY_ENABLED)
            ->addFileContents('EmailLoaderInterface.php', <<<EOF
<?php

interface EmailLoaderInterface
{
    public function getAllEmails();
}
EOF
            , File::MODE_READONLY_ENABLED)
            ->setEntryPointFilename('sendHappy.php')
        ;

        return $builder;
    }

    public function getWorkerConfig(WorkerLoaderInterface $loader)
    {
        return $loader->load(__DIR__.'/../php_worker.yml');
    }

    public function setupContext(CodingContext $context)
    {
        $context->requireFile('EmailLoaderInterface.php');
        $context->requireFile('EmailAddressLoader.php');
        $context->requireFile('HappyMessageSender.php');
    }

    public function grade(CodingExecutionResult $result)
    {
        $phpGrader = new PhpGradingTool($result);
        $htmlGrader = new HtmlOutputGradingTool($result);

        $phpGrader->assertInputDoesNotContain('HappyMessageSender.php', 'global', 'Remove the `global` from `HappyMessageSender` by passing the `$emailLoader` and `$logger` through the `__construct()` function');

        $phpGrader->assertVariableExists('container');
        $container = $result->getDeclaredVariableValue('container');
        $phpGrader->assertInputContains(
            'sendHappy.php',
            'email_loader',
            'Unable to find `email_loader` service. Did you create it?'
        );
        $phpGrader->assertInputContains(
            'sendHappy.php',
            'happy_sender',
            'Unable to find `happy_sender` service. Did you create it?'
        );
        if (!is_subclass_of($container['email_loader'], 'EmailAddressLoader')) {
            throw new GradingException('Seems `email_loader` service isn\'t an instance of the `EmailAddressLoader` class. Check your declaration of this service.');
        }
        if (!is_subclass_of($container['happy_sender'], 'EmailAddressLoader')) {
            throw new GradingException('Seems `email_loader` service isn\'t an instance of the `HappyMessageSender` class. Check your declaration of this service.');
        }

        $htmlGrader->assertOutputContains('GREAT day', 'I don\'t see the "I hope you\'re having a GREAT day" message being printed!?');
        $htmlGrader->assertOutputContains('iluvhappyemails@symfony.com');
    }

    public function configureCorrectAnswer(CorrectAnswer $correctAnswer)
    {
        $correctAnswer
            ->setFileContents('sendHappy.php', <<<EOF
<?php

require __DIR__.'/vendor/autoload.php';

\$container = new Pimple();

\$container['email_loader'] = \$container->share(function() {
    return new EmailAddressLoader();
});
\$container['happy_sender'] = \$container->share(function(Pimple \$container) {
    return new HappyMessageSender(\$container['email_loader']);
});

\$happyMessageSender = \$container['happy_sender'];
\$happyMessageSender->sendHappiness();
EOF
            )
        ;
    }
}
