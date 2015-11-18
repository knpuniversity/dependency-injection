# A Container in your Project

Ok, time to get to emailing! No matter what framework or system you work on,
you can start applying these principles immediately. You may already have
a dependency injection container available to you, and if so, great! If not,
don't worry! Even without a container, you can start applying the principles
of moving code into new service classes and using dependency injection. If
you have to instantiate these service objects manually when you need them,
that's still a huge step forward!

You can also bring a container into your project. Pimple is the simplest and
easiest, but there are also others such as [Symfony's DependencyInjection Component][1],
[Aura Di][2], and [Zend\Di][3]. These are more feature-rich and also contain
speed optimizations.

Somewhere early in your bootstrap process, simply create the container, configure
it, and make it available to your controllers or page code.

If you have any questions or comments, post them! Have fun, and we'll see
you next time!


[1]: http://symfony.com/doc/current/components/dependency_injection/introduction.html
[2]: https://github.com/auraphp/Aura.Di
[3]: http://framework.zend.com/manual/2.0/en/modules/zend.di.introduction.html
