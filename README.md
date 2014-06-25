# PHP library for Voldemort

Protocol buffer bindings for [Voldemort Open Source release 1.6.0](https://github.com/voldemort/voldemort/releases/tag/release-1.6.0-cutoff).

## Voldemort

You can find project Voldemort here: https://github.com/voldemort/voldemort

## Installation

The library can be included using composer. Add the following lines to your project's composer.json:

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/manavo/php-voldemort"
            }
        ],
        "require": {
            "manavo/php-voldemort": "*@dev"
        }
    }

## Tests ##

To run the test suite, run composer (`php composer.phar install`) and then run PHPUnit (`vendor/bin/phpunit`) from the project root.
