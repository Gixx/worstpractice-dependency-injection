# The worstpractice.dev presents

## DIY Dependency Injection

![PHP Version](https://img.shields.io/badge/PHP-7.4-blue)
![Packagist Version](https://flat.badgen.net/packagist/v/gixx/worstpractice-dependency-injection/latest)
[![Build Status](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Gixx/worstpractice-dependency-injection/?branch=master)
[![Packagist Package](https://flat.badgen.net/packagist/name/gixx/worstpractice-dependency-injection)](https://packagist.org/packages/gixx/worstpractice-dependency-injection)
![Packagist Downloads](https://flat.badgen.net/packagist/dt/gixx/worstpractice-dependency-injection)

### Purpose

The only purpose is practicing:
* PHP 7.4 features
* keep coding standards
* write clean code
* write strict-typed code
* Unit testing

Although, I believe it works like any other DIC, I don't recommend to use it in production.   

### Installation

To add this package to your project, just get it via composer:

```
composer require gixx/worstpractice-dependency-injection
```

### Usage

To use it, you will need only a configuration as in the example:

```php
$config = [
    'ServiceAlias' => [
        'class' => \Namespace\To\MyClass::class,
        'arguments' => [
            \Namespace\To\OtherClassInterface::class,
            'literalParameter' => 1234,
            'otherLiteralParameter' => false
        ],
        'shared' => false           
    ],
    \Namespace\To\OtherClassInterface::class => [
        'class' => \Namespace\To\OtherClass::class,
        'shared' => true
    ],
    \DateTimeZone::class => [
        'arguments' => [
            'param' => 'Europe/Berlin'
        ],
        'shared' => true
    ],
    \DateTime::class => [
        'calls' => [
            ['setTimezone', [\DateTimeZone::class]]
        ],
        'shared' => true
    ],
    'Auth' => [
        // empty, will be determined later
    ],
    'OtherServiceAlias' => [
        'inherits' => 'ServiceAlias',
        'calls' => [
            ['someMethod', ['parameter1' => 4543, 'parameter2' => [0, 1, 2], \DateTime::class]]
        ],
        'shared' => true       
    ],
    'LoginController' => [
        'class' => \Namespace\To\Controller\Login:class,
        'arguments' => [
            'Auth',
            'OtherServiceAlias'
        ]   
    ]
];

$container = new \WorstPractice\Component\DependencyInjection\Container($config);

$authService = $_ENV['environment'] === 'dev'
    ? new \Namespace\To\DebugAuthService()
    : new \Namepace\To\Strict\AuthenticationService();
$isShared = true;

$container->set('Auth', $authService, $isShared);

$controller = $container->get('LoginController');
```

This DIC will instantiate any class only when it is requested or being referenced by a requested class. It also 
supports adding instance into the DIC. It comes in handy when the script needs an instance based on some calculation. 

### Testing

The package contains a simple Docker setup to be able to run tests. For this you need only run the following:
```
docker-compose up -d
docker exec -it worstpractice-dependency-injection-php-fpm php composer.phar install
docker exec -it worstpractice-dependency-injection-php-fpm php composer.phar check
```

The following tests will run:
* PHP lint
* PHP Mess Detector
* PHP-CS-Fixer
* PHP Code Sniffer
* PHP Unit
* PHPStan
