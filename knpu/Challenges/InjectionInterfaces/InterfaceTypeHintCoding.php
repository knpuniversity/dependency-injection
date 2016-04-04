<?php

namespace Challenges\InjectionInterfaces;

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

class InterfaceTypeHintCoding implements CodingChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
Ah, new requirements! The `EmailAddressLoader` just returns
some hardcoded emails. That's ok for now. But eventually, you're
going to create a `DatabaseEmailAddressLoader` that will pull
the emails from the database.

In preparation for this, you've created a new interface called
`EmailLoaderInterface`. Update `EmailLoader` to implement this
and then change the type-hint in `HappyMessageSender` to allow
*any* object that implements this interface.
EOF;
    }

    public function getChallengeBuilder()
    {
        $builder = new ChallengeBuilder();
        $builder
            ->addFileContents('EmailAddressLoader.php', <<<EOF
<?php

class EmailAddressLoader
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
            )
            ->addFileContents('HappyMessageSender.php', <<<EOF
<?php

class HappyMessageSender
{
    private \$emailLoader;

    private \$logger;

    public function __construct(EmailAddressLoader \$emailLoader, Logger \$logger)
    {
        \$this->emailLoader = \$emailLoader;
        \$this->logger = \$logger;
    }

    public function sendHappiness()
    {
        \$emails = \$this->emailLoader->getAllEmails();
        foreach (\$emails as \$email) {
            // just print for testing
            echo 'I hope you\'re having a GREAT day '.\$email.'<hr/>';
            \$this->logger->logMessage(\$email);
        }
    }
}
EOF
            )
            ->addFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$logger = new Logger(__DIR__.'/debug.log');
\$happyMessageSender = new HappyMessageSender(\$emailLoader, \$logger);

\$happyMessageSender->sendHappiness();
EOF
            , File::MODE_READONLY_ENABLED)
            ->addFileContents('Logger.php', <<<EOF
<?php

class Logger
{
    private \$logPath;

    public function __construct(\$logPath)
    {
        \$this->logPath = \$logPath;
    }

    public function logMessage(\$message)
    {
        \$fh = fopen(\$this->logPath, 'a');
        fwrite(\$fh, \$message.PHP_EOL);
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
        $context->requireFile('Logger.php');
    }

    public function grade(CodingExecutionResult $result)
    {
        $phpGrader = new PhpGradingTool($result);
        $htmlGrader = new HtmlOutputGradingTool($result);

        $phpGrader->assertInputDoesNotContain('HappyMessageSender.php', 'global', 'Remove the `global` from `HappyMessageSender` by passing the `$emailLoader` and `$logger` through the `__construct()` function');

        $loaderClass = new \ReflectionClass('EmailAddressLoader');
        if (!$loaderClass->implementsInterface('EmailLoaderInterface')) {
            throw new GradingException('The `EmailAddressLoader` class should implement the `EmailLoaderInterface` interface.');
        }
        $senderClass = new \ReflectionClass('HappyMessageSender');
        if (!$senderClass->hasMethod('__construct')) {
            throw new GradingException('Unable to find `__construct()` method in the `HappyMessageSender` class. Did you remove it?');
        }
        $senderConstruct = $senderClass->getMethod('__construct');
        $parameters = $senderConstruct->getParameters();
        if (false
            || false === isset($parameters[0])
            || null === $parameters[0]->getClass()
            || 0 !== strcmp('EmailLoaderInterface', $parameters[0]->getClass()->getName())
        ) {
            throw new GradingException('You should change type hint of `$emailLoader` first parameter to the `EmailLoaderInterface` interface in `HappyMessageSender::__construct()`.');
        }

        $htmlGrader->assertOutputContains('GREAT day', 'I don\'t see the "I hope you\'re having a GREAT day" message being printed!?');
        $htmlGrader->assertOutputContains('iluvhappyemails@symfony.com');
    }

    public function configureCorrectAnswer(CorrectAnswer $correctAnswer)
    {
        $correctAnswer
            ->setFileContents('HappyMessageSender.php', <<<EOF
<?php

class HappyMessageSender
{
    private \$emailLoader;

    private \$logger;

    public function __construct(EmailLoaderInterface \$emailLoader, Logger \$logger)
    {
        \$this->emailLoader = \$emailLoader;
        \$this->logger = \$logger;
    }

    public function sendHappiness()
    {
        \$emails = \$this->emailLoader->getAllEmails();
        foreach (\$emails as \$email) {
            // just print for testing
            echo 'I hope you\'re having a GREAT day '.\$email.'<hr/>';
            \$this->logger->logMessage(\$email);
        }
    }
}
EOF
            )
            ->setFileContents('EmailAddressLoader.php', <<<EOF
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
            )
        ;
    }
}
