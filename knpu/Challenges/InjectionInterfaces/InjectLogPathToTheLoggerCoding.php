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

class InjectLogPathToTheLoggerCoding implements CodingChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
Logging, check! But now we have a problem. When we deploy
to production, we will want to log to a different file. But
right now the path to the log file is *hardcoded* in `Logger`.
Let's make it more flexible!

Give `Logger` a `__construct()` function and allow the `\$logPath`
to be passed in as an argument and set on a property (call the
property `logPath`). Update `sendHappy.php` to pass the existing
`__DIR__.'/debug.log'` path into the object.
EOF;
    }

    public function getChallengeBuilder()
    {
        $builder = new ChallengeBuilder();
        $builder
            ->addFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$logger = new Logger();
\$happyMessageSender = new HappyMessageSender(\$emailLoader, \$logger);

\$happyMessageSender->sendHappiness();
EOF
            )
            ->addFileContents('Logger.php', <<<EOF
<?php

class Logger
{
    public function logMessage(\$message)
    {
        \$logPath = __DIR__.'/debug.log';

        \$fh = fopen(\$logPath, 'a');
        fwrite(\$fh, \$message.PHP_EOL);
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
            , File::MODE_READONLY_ENABLED)
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
        $context->requireFile('EmailAddressLoader.php');
        $context->requireFile('HappyMessageSender.php');
        $context->requireFile('Logger.php');
    }

    public function grade(CodingExecutionResult $result)
    {
        $phpGrader = new PhpGradingTool($result);
        $htmlGrader = new HtmlOutputGradingTool($result);

        $phpGrader->assertInputDoesNotContain('HappyMessageSender.php', 'global', 'Remove the `global` from `HappyMessageSender` by passing the `$emailLoader` and `$logger` through the `__construct()` function');

        $loggerClass = new \ReflectionClass('Logger');
        if (!$loggerClass->hasProperty('logPath')) {
            throw new GradingException('The `logPath` property not found in the `Logger` class. Did you create it?');
        }
        $logPathProperty = $loggerClass->getProperty('logPath');
        if (!$logPathProperty->isPrivate()) {
            throw new GradingException('Lets make a `logPath` property `private`. We *really* don\'t want to be able to override it outside of the `Logger` class in this case.');
        }
        if (!$loggerClass->hasMethod('__construct')) {
            throw new GradingException('Seems you don\'t add a `__construct()` method in the `Logger` class. Lets create it!');
        }
        $loggerConstructor = $loggerClass->getConstructor();
        $parameters = $loggerConstructor->getParameters();
        if (false
            || 0 === $loggerConstructor->getNumberOfParameters()
            || 0 !== strcmp('logPath', $parameters[0]->getName())
        ) {
            throw new GradingException('You should get a `$logPath` as a first argument in `__construct()` method of the `Logger` class.');
        }

        $phpGrader->assertInputContains('Logger.php', '$this->logPath', 'Be sure to use `logPath` property in the `Logger` class.');

        $htmlGrader->assertOutputContains('GREAT day', 'I don\'t see the "I hope you\'re having a GREAT day" message being printed!?');
        $htmlGrader->assertOutputContains('iluvhappyemails@symfony.com');
    }

    public function configureCorrectAnswer(CorrectAnswer $correctAnswer)
    {
        $correctAnswer
            ->setFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$logger = new Logger(__DIR__.'/debug.log');
\$happyMessageSender = new HappyMessageSender(\$emailLoader, \$logger);

\$happyMessageSender->sendHappiness();
EOF
            )
            ->setFileContents('Logger.php', <<<EOF
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
            )
        ;
    }
}
