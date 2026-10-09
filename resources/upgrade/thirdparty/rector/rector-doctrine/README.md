# Rector Rules for Doctrine

See available [Doctrine rules](https://flamesphp.com/find-rule?activeRectorSetGroup=doctrine)

## Install

This package is already part of [flamesphp/code](http://github.com/rectorphp/rector) package, so it works out of the box.

All you need to do is install the main package, and you're good to go:

```bash
composer require flamesphp/code --dev
```

## Use Sets

To add a set to your config, use `->withPreparedSets` method, and pick one of constants:

```php
use Flames\Code\Upgrade\Config\UpgradeConfig;

return UpgradeConfig::configure()
    ->withPreparedSets(doctrineCodeQuality: true)
    ->withComposerBased(doctrine: true);
```

If you're on PHP 7.x, you can use withSets() instead, for `doctrineCodeQuality` set, so you can define:

```php
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Doctrine\Set\DoctrineSetList;

return UpgradeConfig::configure()
    ->withSets([
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);
```

See [documentation](https://flamesphp.com/documentation) for more.

<br>

## Learn Rector Faster

Rector is a tool that [we develop](https://flamesphp.com/) and share for free, so anyone can save hundreds of hours on refactoring. But not everyone has time to understand Rector and AST complexity. You have 2 ways to speed this process up:

* read a book - <a href="https://leanpub.com/rector-the-power-of-automated-refactoring">The Power of Automated Refactoring</a>
* hire our experienced team to <a href="https://flamesphp.com/contact">improve your code base</a>

Both ways support us to and improve Rector in sustainable way by learning from practical projects.
