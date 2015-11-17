<?php

namespace Challenges\Services;

use KnpU\Gladiator\MultipleChoice\AnswerBuilder;
use KnpU\Gladiator\MultipleChoiceChallengeInterface;

class WhatsWrongWithGlobalMC implements MultipleChoiceChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
You fixed Bob's code to not use globals anymore.
"But wait!" - he says - "What's so wrong with using
globals? My code was a lot easier before!".
EOF;

    }

    public function configureAnswers(AnswerBuilder $builder)
    {
        $builder->addAnswer('Globals are deprecated and are eventually going to be removed from PHP.')
            ->addAnswer('Globals make your code difficult to debug and read. When you have `global $emailLoader`, I wonder - who set this variable? And where? What type of object is this?', true)
            ->addAnswer('Globals are not as performant as using dependency injection.')
            ->addAnswer('The `$GLOBALS` variable is available when using a web server, but *not* when running command-line tasks. This makes your code very breakable if you want to use the same code to do batch processing jobs.')
        ;
    }

    public function getExplanation()
    {
        return <<<EOF
The only true statement is (B): globals makes your code difficult
to debug, understand and maintain. When using `global \$emailLoader`,
it's not clear where this is created or who created it. And what's
worse, in a different file, you may innocently rename or remove
`\$emailLoader` not realizing that many other parts of your code
are relying on this being available globally!

Writing code where you pass in needed values (dependency injection)
takes more work. The pay-off are classes that are easier to read
and a lot more predictable (and more unit-testable, etc).
EOF;
    }
}
