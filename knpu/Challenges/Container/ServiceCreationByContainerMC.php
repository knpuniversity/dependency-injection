<?php

namespace Challenges\Services;

use KnpU\Gladiator\MultipleChoice\AnswerBuilder;
use KnpU\Gladiator\MultipleChoiceChallengeInterface;

class ServiceCreationByContainerMC implements MultipleChoiceChallengeInterface
{
    public function getQuestion()
    {
        return <<<EOF
Check out the following code:

```php
\$container = new Pimple();
\$container['email_loader'] = \$container->share(function() {
    return new EmailAddressLoader();
});

\$loader1 = \$container['email_loader'];
\$loader2 = \$container['email_loader'];
```

Which of the following is most accurate?
EOF;
    }

    public function configureAnswers(AnswerBuilder $builder)
    {
        $builder
            ->addAnswer('The `$loader1` and `$loader2` objects are the exact same object in memory.', true)
            ->addAnswer('The container creates 2 new `EmailAddressLoader` objects: one each time we ask for the `email_loader` service.')
            ->addAnswer('The container creates *one* `email_loader` object when the container is loaded, then returns just that one object each time we ask for the `email_loader` service.')
            ->addAnswer('The container creates *zero* `EmailAddressLoader` objects: it doesn\'t create them until you actually call a method on the object.')
        ;
    }

    public function getExplanation()
    {
        return <<<EOF
A container gives us two awesome things:

1. When you ask for `email_loader`, it is only ever created once
2. The `EmailAddressLoader` is *not* created until (and unless) we ask for the `email_loader` service.
EOF;
    }
}
