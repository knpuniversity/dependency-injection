<?php

namespace Challenges\Services;

use KnpU\Gladiator\CodingChallenge\ChallengeBuilder;
use KnpU\Gladiator\CodingChallenge\CodingContext;
use KnpU\Gladiator\CodingChallenge\CodingExecutionResult;
use KnpU\Gladiator\CodingChallenge\CorrectAnswer;
use KnpU\Gladiator\CodingChallenge\Exception\GradingException;
use KnpU\Gladiator\CodingChallengeInterface;
use KnpU\Gladiator\Grading\HtmlOutputGradingTool;
use KnpU\Gladiator\Grading\PhpGradingTool;
use KnpU\Gladiator\Worker\WorkerLoaderInterface;

class RefactorOutGlobalCoding implements CodingChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
The intern (Bob) is *awesome*. Or so you thought! Until you
see this code, which uses a `global` variable! The horror!
Help out Bob by showing him how to correctly pass the
`\$emailRepository` object via dependency injection so that
the dreaded `global` isn't needed!
EOF;

    }

    public function getChallengeBuilder()
    {
        $builder = new ChallengeBuilder();
        $builder->addFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$GLOBALS['emailLoader'] = \$emailLoader;

\$happyMessageSender = new HappyMessageSender();

\$happyMessageSender->sendHappiness();
EOF
        )
        ->setEntryPointFilename('sendHappy.php')
        ->addFileContents('HappyMessageSender.php', <<<EOF
<?php

class HappyMessageSender
{
    public function sendHappiness()
    {
        global \$emailLoader;

        \$emails = \$emailLoader->getAllEmails();
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
    }

    public function grade(CodingExecutionResult $result)
    {
        $phpGrader = new PhpGradingTool($result);
        $htmlGrader = new HtmlOutputGradingTool($result);

        $phpGrader->assertInputDoesNotContain('HappyMessageSender.php', 'global', 'Remove the `global` from HappyMessageSender by passing the `$emailLoader` through the `__construct()` function');
        $phpGrader->assertInputContains('HappyMessageSender.php', '__construct', 'Be sure to add a __construct() method to HappyMessageSender with an `$emailLoader` argument.');

        $htmlGrader->assertOutputContains('GREAT day', 'I don\'t see the "I hope you\'re having a GREAT day" message being printed!?');
        $htmlGrader->assertOutputContains('iluvhappyemails@symfony.com');

        $phpGrader->assertInputDoesNotContain('sendHappy.php', '$GLOBALS', 'Get rid of the $GLOBALS in sendHappy.php - we don\'t need that anymore (yay!)');

    }

    public function configureCorrectAnswer(CorrectAnswer $correctAnswer)
    {
        $correctAnswer->setFileContents('HappyMessageSender.php', <<<EOF
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
        );

        $correctAnswer->setFileContents('sendHappy.php', <<<EOF
<?php

\$emailLoader = new EmailAddressLoader();
\$happyMessageSender = new HappyMessageSender(\$emailLoader);

\$happyMessageSender->sendHappiness();
EOF
        );
    }

}