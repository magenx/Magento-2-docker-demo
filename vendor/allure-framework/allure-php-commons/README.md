# Allure PHP Commons

[![Version](http://poser.pugx.org/allure-framework/allure-php-commons/version)](https://packagist.org/packages/allure-framework/allure-php-commons)
[![Build](https://github.com/allure-framework/allure-php-commons2/actions/workflows/build.yml/badge.svg)](https://github.com/allure-framework/allure-php-commons2/actions/workflows/build.yml)
[![Type Coverage](https://shepherd.dev/github/allure-framework/allure-php-commons2/coverage.svg)](https://shepherd.dev/github/allure-framework/allure-php-commons2)
[![Psalm Level](https://shepherd.dev/github/allure-framework/allure-php-commons2/level.svg)](https://shepherd.dev/github/allure-framework/allure-php-commons2)
[![License](http://poser.pugx.org/allure-framework/allure-php-commons/license)](https://packagist.org/packages/allure-framework/allure-php-commons)

This repository contains PHP API for Allure framework. The main idea is to reuse this API when creating adapters for different test frameworks.

## Getting started
In order to use this API you simply need to add the following to **composer.json**:
```json
{
    "require": {
        "php": "^8",
        "allure-framework/allure-php-commons": "^2"
    }
}
```

## Custom attributes
You can easily implement custom attributes and use them with your test framework. In most cases you would like
to implement [`Qameta\Allure\Attribute\AttributeSetInterface`](./src/Attribute/AttributeSetInterface.php) that allows to set several attributes at once:

```php
<?php

use Qameta\Allure\Attribute\AttributeSetInterface;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Tag;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class MyAttribute implements AttributeSetInterface
{
    private array $tags;

    public function __construct(
        private string $displayName,
        string ...$tags,
    ) {
        $this->tags = $tags;
    }
    
    public function getAttributes() : array
    {
        return [
            new DisplayName($this->displayName),
            ...array_map(
                fn (string $tag): Tag => new Tag($tag),
                $this->tags,
            ),
        ];
    }
}

// Example of usage
#[MyAttribute('Test name', 'tag 1', 'tag 2')]
class MyTestClass
{
}
```

You can also implement particular attribute interfaces instead of using one of the standard implementations:

- [`Qameta\Allure\Attribute\DescriptionInterface`](./src/Attribute/DescriptionInterface.php)
- [`Qameta\Allure\Attribute\DisplayNameInterface`](./src/Attribute/DisplayNameInterface.php)
- [`Qameta\Allure\Attribute\LabelInterface`](./src/Attribute/LabelInterface.php)
- [`Qameta\Allure\Attribute\LinkInterface`](./src/Attribute/LinkInterface.php)
- [`Qameta\Allure\Attribute\ParameterInterface`](./src/Attribute/ParameterInterface.php)

## Other usage examples
See [allure-phpunit](https://github.com/allure-framework/allure-phpunit)
and [allure-codeception](https://github.com/allure-framework/allure-codeception) projects.
