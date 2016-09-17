# aedart/config

[![Build Status](https://travis-ci.org/aedart/config.svg?branch=master)](https://travis-ci.org/aedart/config)
[![Latest Stable Version](https://poser.pugx.org/aedart/config/v/stable)](https://packagist.org/packages/aedart/config)
[![Total Downloads](https://poser.pugx.org/aedart/config/downloads)](https://packagist.org/packages/aedart/config)
[![Latest Unstable Version](https://poser.pugx.org/aedart/config/v/unstable)](https://packagist.org/packages/aedart/config)
[![License](https://poser.pugx.org/aedart/config/license)](https://packagist.org/packages/aedart/config)

Package contains various configuration utilities.

## Contents

* [How to install](#how-to-install)
* [Parsers](#parsers)
  * [Reference Parser](#reference-parser)
* [Contribution](#contribution)
* [Acknowledgement](#acknowledgement)
* [Versioning](#versioning)
* [License](#license)

## How to install

This package uses [composer](https://getcomposer.org/). If you do not know what that is or how it works, I recommend that you read a little about, before attempting to use this package.

```console
composer require aedart/config
```

## Parsers

### Reference Parser

`\Aedart\Config\Parsers\ReferenceParser`

Able of parsing "references" in values.

```php
<?php

use Aedart\Config\Parsers\ReferenceParser;
use Illuminate\Config\Repository;

// Given the following array
$items = [
    'db.driver'         => '{{defaults.driver}}',
    'defaults.driver'   => 'abc'
];
 
// When it is parsed
$repo   = new Repository($items);
$config = (new ReferenceParser())->parse($repo);
 
// The 'db.driver' key is parsed to the value of 'defaults.driver'
echo $config->get('db.driver'); // output 'abc'
```

**Warning**: Parsing references can cost a lot of processing power. You should cache the result whenever it is possible!

For further references, please consider the unit test; `tests\unit\parsers\ReferenceParserTest.php`

--------------------------

## Contribution

Have you found a defect ( [bug or design flaw](https://en.wikipedia.org/wiki/Software_bug) ), or do you wish improvements? In the following sections, you might find some useful information
on how you can help this project. In any case, I thank you for taking the time to help me improve this project's deliverables and overall quality.

### Bug Report

If you are convinced that you have found a bug, then at the very least you should create a new issue. In that given issue, you should as a minimum describe the following;

* Where is the defect located
* A good, short and precise description of the defect (Why is it a defect)
* How to replicate the defect
* (_A possible solution for how to resolve the defect_)

When time permits it, I will review your issue and take action upon it.

### Fork, code and send pull-request

A good and well written bug report can help me a lot. Nevertheless, if you can or wish to resolve the defect by yourself, here is how you can do so;

* Fork this project
* Create a new local development branch for the given defect-fix
* Write your code / changes
* Create executable test-cases (prove that your changes are solid!)
* Commit and push your changes to your fork-repository
* Send a pull-request with your changes
* _Drink a [Beer](https://en.wikipedia.org/wiki/Beer) - you earned it_ :)

As soon as I receive the pull-request (_and have time for it_), I will review your changes and merge them into this project. If not, I will inform you why I choose not to.

## Acknowledgement

* [ PHP ](http://php.net/), `Rasmus Lerdorf & The PHP Group`; we might be developing in old fashioned ASP… (Shivers!)


## Versioning

This package follows [Semantic Versioning 2.0.0](http://semver.org/)

## License

[BSD-3-Clause](http://spdx.org/licenses/BSD-3-Clause), Read the LICENSE file included in this package
