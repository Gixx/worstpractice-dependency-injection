<?php

/**
 * Worst Practice DI Component
 *
 * PHP version 7.4
 *
 * @copyright 2020 Worst Practice
 * @license   https://opensource.org/licenses/MIT The MIT License (MIT)
 *
 * @link http://www.worstpractice.dev
 */

declare(strict_types=1);

namespace WorstPracticeTest\Component\DependencyInjection;

use WorstPractice\Component\DependencyInjection\Container;
use WorstPractice\Component\DependencyInjection\ContainerInterface;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * Class ContainerTest
 * @package WorstPracticeTest\Component\DependencyInjection
 */
class ContainerTest extends TestCase
{
    /**
     * Tests if the constructor creates the right instance.
     */
    public function testConstructor(): void
    {
        $container = new Container([]);

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    /**
     * Test if the container can instantiate built-in class without configuration.
     */
    public function testContainerRetrievingBuiltInClass(): void
    {
        $container = new Container([]);

        $this->assertTrue($container->has(DateTime::class));
        $actualObject = $container->get(DateTime::class);
        $this->assertInstanceOf(DateTime::class, $actualObject);
    }

    /**
     * Test if the container will throw an exception for a non-existing class.
     */
    public function testContainerGetErrorForNonExistingClass(): void
    {
        $container = new Container([]);

        $nonExistingClass = '\\Namespace\\To\\Non\\Existing\\Class\\A' . md5('something');

        $this->assertFalse($container->has($nonExistingClass));
        $this->expectException(\OutOfBoundsException::class);
        $container->get($nonExistingClass);
    }

    /**
     * Test if the container will throw an error for a non-existing class reference in the configuration.
     */
    public function testContainerGetErrorForNonExistingClassInConfig(): void
    {
        $config = [
            'ServiceAlias' => [
                'class' => '\\Namespace\\To\\Non\\Existing\\Class\\A' . md5('something'),
            ]
        ];

        $container = new Container($config);

        $nonExistingClass = '\\Namespace\\To\\Non\\Existing\\Class\\A' . md5('something');

        $this->assertTrue($container->has('ServiceAlias'));
        $this->expectException(\RuntimeException::class);
        $container->get('ServiceAlias');
    }

    /**
     * Test if an alias for a class will create a different instance from getting the class directly.
     */
    public function testContainerAliasIsDifferentInstance(): void
    {
        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => '1980-02-19 12:15:00',
                ]
            ]
        ];

        $container = new Container($config);

        $this->assertTrue($container->has('DateService'));
        $this->assertTrue($container->has(DateTime::class));

        $aliasedDate = $container->get('DateService');
        $anotherDate = $container->get(DateTime::class);

        $this->assertNotSame($aliasedDate->getTimestamp(), $anotherDate->getTimestamp());
    }

    /**
     * Test if container will get a new instance of a class if it's configured to be not shared.
     */
    public function testNotSharedInstances(): void
    {
        $dateTimeString = '1980-02-19 12:15:00';

        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateString' => $dateTimeString,
                ],
                'shared' => false
            ]
        ];

        $container = new Container($config);

        $date1 = $container->get('DateService');
        $this->assertSame($dateTimeString, $date1->format('Y-m-d H:i:s'));

        $date2 = $container->get('DateService');
        $date2->setTimestamp(time());

        $this->assertNotSame($date1->format('Y-m-d H:i:s'), $date2->format('Y-m-d H:i:s'));
    }

    /**
     * Test if container will return with the same instance if it's configured to be shared.
     */
    public function testSharedInstance(): void
    {
        $dateTimeString = '1980-02-19 12:15:00';

        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateString' => $dateTimeString,
                ],
                'shared' => true
            ]
        ];

        $container = new Container($config);

        $date1 = $container->get('DateService');
        $this->assertSame($dateTimeString, $date1->format('Y-m-d H:i:s'));

        $date2 = $container->get('DateService');
        $date2->setTimestamp(time());

        $this->assertSame($date1->format('Y-m-d H:i:s'), $date2->format('Y-m-d H:i:s'));
    }

    /**
     * Test if container allows to overwrite a service on-the-fly before the first instantiation
     */
    public function testContainerOverwriteService(): void
    {
        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => '1980-02-19 12:15:00'
                ],
                'shared' => false
            ]
        ];

        $container = new Container($config);

        $this->assertTrue($container->has('DateService'));

        $dateTimeString = '2000-01-01 01:01:01';
        $date1 = new DateTime($dateTimeString);

        $container->set('DateService', $date1);
        $date2 = $container->get('DateService');

        $this->assertSame($date1->format('Y-m-d H:i:s'), $date2->format('Y-m-d H:i:s'));
    }

    /**
     * Test if container blocks to overwrite a service on-the-fly after the first instantiation
     */
    public function testContainerFailingDoubleInstantiation(): void
    {
        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => '1980-02-19 12:15:00'
                ],
                'shared' => false
            ]
        ];

        $container = new Container($config);

        $this->assertTrue($container->has('DateService'));

        $date1 = $container->get('DateService');
        $date2 = new DateTime();

        $this->assertNotSame($date1->format('Y-m-d H:i:s'), $date2->format('Y-m-d H:i:s'));

        $this->expectException(\RuntimeException::class);
        $container->set('DateService', $date2);
    }

    /**
     * Test if container handles config inheritance with overwrite possibility.
     */
    public function testContainerInheritance(): void
    {
        $config = [
            'SharedDateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => '1980-02-19 12:15:00'
                ],
                'shared' => true
            ],
            'NotSharedDateService' => [
                'inherits' => 'SharedDateService',
                'shared' => false
            ],
            DateTime::class => [
                'inherits' => 'SharedDateService',
            ]
        ];

        $container = new Container($config);

        $sharedDate1 = $container->get('SharedDateService');
        $notSharedDate1 = $container->get('NotSharedDateService');
        $this->assertSame($sharedDate1->format('Y-m-d H:i:s'), $notSharedDate1->format('Y-m-d H:i:s'));

        $sharedDate2 = $container->get('SharedDateService');
        $sharedDate2->setTimestamp(time());
        $this->assertSame($sharedDate1->format('Y-m-d H:i:s'), $sharedDate2->format('Y-m-d H:i:s'));
        $this->assertNotSame($sharedDate1->format('Y-m-d H:i:s'), $notSharedDate1->format('Y-m-d H:i:s'));
        $this->assertNotSame($sharedDate2->format('Y-m-d H:i:s'), $notSharedDate1->format('Y-m-d H:i:s'));

        $notSharedDate2 = $container->get('NotSharedDateService');
        $notSharedDate2->setTimestamp(time());
        $this->assertNotSame($notSharedDate1->format('Y-m-d H:i:s'), $notSharedDate2->format('Y-m-d H:i:s'));

        $sharedDate3 = $container->get(DateTime::class);
        $this->assertNotSame($sharedDate1->format('Y-m-d H:i:s'), $sharedDate3->format('Y-m-d H:i:s'));
    }

    /**
     * Test if container will call the given methods with the given arguments after instantiate a class.
     */
    public function testContainerConfigCalls(): void
    {
        $dateTimeString = '1980-02-19 12:15:00';
        $timeZoneString = 'Europe/Berlin';

        $config = [
            DateTimeZone::class => [
                'arguments' => [
                    'timezone' => $timeZoneString
                ]
            ],
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => $dateTimeString
                ],
                'calls' => [
                    ['setDate', ['year' => 2000, 'month' => 3, 'day' => 24]],
                    ['setTimezone', [DateTimeZone::class]]
                ],
                'shared' => true
            ],
        ];

        $container = new Container($config);

        $date = $container->get('DateService');
        $this->assertSame('2000-03-24', $date->format('Y-m-d'));

        $timeZone = $date->getTimezone();
        $this->assertSame($timeZoneString, $timeZone->getName());
    }

    /**
     * Test container fails instantiate a class when the class config contains invalid method reference.
     */
    public function testContainerFailingConfigCalls1(): void
    {
        $dateTimeString = '1980-02-19 12:15:00';

        $config = [
            'DateService' => [
                'class' => DateTime::class,
                'arguments' => [
                    'dateTimeString' => $dateTimeString
                ],
                'calls' => [
                    ['setDatum', ['year' => 2000, 'month' => 3, 'day' => 24]],
                ],
                'shared' => true
            ],
        ];

        $container = new Container($config);

        $this->expectException(\RuntimeException::class);
        $date = $container->get('DateService');
    }
}
