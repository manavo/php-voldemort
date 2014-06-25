# PHP library for Voldemort

## Installation

This set of libraries can be included using composer. Add the following lines to your project's composer.json:

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

To run the test suite, run composer (`php composer.phar install --dev`) and then run PHPUnit (`vendor/bin/phpunit`) from the project root.
