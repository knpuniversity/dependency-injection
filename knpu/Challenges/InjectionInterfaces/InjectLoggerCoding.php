<?php

namespace Challenges\InjectionInterfaces;

use KnpU\Gladiator\CodingChallenge\ChallengeBuilder;
use KnpU\Gladiator\CodingChallenge\CodingContext;
use KnpU\Gladiator\CodingChallenge\CodingExecutionResult;
use KnpU\Gladiator\CodingChallenge\CorrectAnswer;
use KnpU\Gladiator\CodingChallenge\Exception\GradingException;
use KnpU\Gladiator\CodingChallengeInterface;
use KnpU\Gladiator\Grading\HtmlOutputGradingTool;
use KnpU\Gladiator\Grading\PhpGradingTool;
use KnpU\Gladiator\Worker\WorkerLoaderInterface;

class InjectLoggerCoding implements CodingChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
When we send our happy messages, we also want to log all
the people that we're emailing. To do that, we've created
a new `Logger` class. Create a new instance of `Logger`
and pass it into `HappyMessageSender`. Inside the `foreach`,
log the email address of each person you're greeting.
EOF;
    }

    public function getChallengeBuilder()
    {
        $builder = new ChallengeBuilder();
        $builder
            ->addFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$happyMessageSender = new HappyMessageSender(\$emailLoader);

\$happyMessageSender->sendHappiness();
EOF
            )
            ->addFileContents('HappyMessageSender.php', <<<EOF
<?php

class HappyMessageSender
{
    private \$emailLoader;

    public function __construct(EmailAddressLoader \$emailLoader)
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
            )
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
            , true)
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
            , true)
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

        $phpGrader->assertInputContains('sendHappy.php', 'new Logger', 'Be sure to create an instance of the `Logger` class in `sendHappy.php`.');
        $phpGrader->assertInputDoesNotContain('HappyMessageSender.php', 'global', 'Remove the `global` from `HappyMessageSender` by passing the `$emailLoader` and `$logger` through the `__construct()` function');
        $phpGrader->assertInputContains('HappyMessageSender.php', '__construct', 'Be sure that a `__construct()` method in the `HappyMessageSender` takes an `$emailLoader` and `$logger` required arguments.');

        $happyMessageSenderClass = new \ReflectionClass('HappyMessageSender');
        $happyMessageSenderConstruct = $happyMessageSenderClass->getConstructor();

        $happyMessageSenderParameters = $happyMessageSenderConstruct->getParameters();
        if (false
            || 2 !== $happyMessageSenderConstruct->getNumberOfRequiredParameters()
            || 0 !== strcmp('emailLoader', $happyMessageSenderParameters[0]->getName())
            || 0 !== strcmp('logger', $happyMessageSenderParameters[1]->getName())
        ) {
            throw new GradingException('Be sure to add a `__construct()` method to `HappyMessageSender` with an `$emailLoader` as a first and `$logger` as a second required argument.');
        }
        if (false
            || null === $happyMessageSenderParameters[0]->getClass()
            || 0 !== strcmp('EmailAddressLoader', $happyMessageSenderParameters[0]->getClass()->getName())
        ) {
            throw new GradingException('Be sure to add `EmailAddressLoader` type hint for first argument of `__construct()` method in the `HappyMessageSender` class.');
        }
        if (false
            || null === $happyMessageSenderParameters[1]->getClass()
            || 0 !== strcmp('Logger', $happyMessageSenderParameters[1]->getClass()->getName())
        ) {
            throw new GradingException('Be sure to add `Logger` type hint for second argument of `__construct()` method in the `HappyMessageSender` class.');
        }
        $phpGrader->assertInputContains('HappyMessageSender.php', '->logMessage', 'Be sure to log the email address of each person you\'re greeting inside the `foreach` in `HappyMessageSender::sendHappiness()`.');

        $htmlGrader->assertOutputContains('GREAT day', 'I don\'t see the "I hope you\'re having a GREAT day" message being printed!?');
        $htmlGrader->assertOutputContains('iluvhappyemails@symfony.com');
    }

    public function configureCorrectAnswer(CorrectAnswer $correctAnswer)
    {
        $correctAnswer
            ->setFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$logger = new Logger();
\$happyMessageSender = new HappyMessageSender(\$emailLoader, \$logger);

\$happyMessageSender->sendHappiness();
EOF
            )
            ->setFileContents('HappyMessageSender.php', <<<EOF
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
        ;
    }
}
