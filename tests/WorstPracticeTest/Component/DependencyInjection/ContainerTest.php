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
use OutOfBoundsException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use WorstPracticeTest\Fixtures;

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
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionCode(1001);
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

        $this->assertTrue($container->has('ServiceAlias'));
        $this->expectException(RuntimeException::class);
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
    public function testContainerWithNotSharedInstances(): void
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
    public function testContainerWithSharedInstance(): void
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
    public function testContainerWithInjectedInstance(): void
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

        $date1 = new DateTime('2000-01-01 01:01:01');

        $container->set('DateService', $date1, false);
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

        $this->expectException(RuntimeException::class);
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
     * Test is container handles config with mutual inheritance.
     */
    public function testContainerInheritanceLoopError(): void
    {
        $config = [
            'SharedDateService' => [
                'inherits' => 'IntermediateService',
                'shared' => true
            ],
            'IntermediateService' => [
                'inherits' => 'NotSharedDateService',
            ],
            'NotSharedDateService' => [
                'inherits' => 'SharedDateService',
                'shared' => false
            ],
        ];

        $container = new Container($config);

        $this->expectException(RuntimeException::class);
        $container->get('NotSharedDateService');
    }

    /**
     * Test if container handles config with self-inheritance.
     */
    public function testContainerInheritanceSelfReferenceError(): void
    {
        $config = [
            'NotSharedDateService' => [
                'inherits' => 'NotSharedDateService',
                'shared' => false
            ],
        ];

        $container = new Container($config);

        $this->expectException(RuntimeException::class);
        $container->get('NotSharedDateService');
    }

    /**
     * Test if container handles config with non-existing inheritance reference.
     */
    public function testContainerInheritanceFalseReferenceError(): void
    {
        $config = [
            'NotSharedDateService' => [
                'inherits' => 'NotExistingService',
                'shared' => false
            ],
        ];

        $container = new Container($config);

        $this->expectException(RuntimeException::class);
        $container->get('NotSharedDateService');
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
    public function testContainerFailingWithInvalidMethodReference(): void
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

        $this->expectException(RuntimeException::class);
        $container->get('DateService');
    }

    /**
     * Test case when the prepared config references to a service which will be injected later.
     */
    public function testContainerConfigReferencesAnInjectedService(): void
    {
        $config = [
            'm-service' => [
                'class' => Fixtures\ClassM::class,
                'arguments' => [
                    'n-service'
                ],
            ],
        ];

        $container = new Container($config);
        $this->assertFalse($container->has('n-service'));

        $date = new DateTime('2000-01-01 01:01:01');
        $serviceN = new Fixtures\ClassN($date);

        $container->set('n-service', $serviceN, true);

        $actualInstance = $container->get('m-service');
        $this->assertInstanceOf(Fixtures\ClassM::class, $actualInstance);
    }

    /**
     * Test container fails instantiate a class when the class config contains invalid method reference.
     */
    public function testContainerFailingWithReferenceLoop(): void
    {
        $config = [
            'a-service' => [
                'class' => Fixtures\ClassA::class,
                'arguments' => [
                    'b-service'
                ],
            ],
            'b-service' => [
                'class' => Fixtures\ClassB::class,
                'arguments' => [
                    'c-service'
                ],
            ],
            'c-service' => [
                'class' => Fixtures\ClassC::class,
                'arguments' => [
                    'd-service'
                ],
            ],
            'd-service' => [
                'class' => Fixtures\ClassD::class,
                'arguments' => [
                    'a-service'
                ],
            ],
        ];

        $container = new Container($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1000);
        $container->get('a-service');
    }

    /**
     * Test container gets the service when two separated reference has the same third reference.
     */
    public function testContainerAllowsTheSameReferenceOnDifferentNodes(): void
    {
        $config = [
            'a-date-service' => [
                'class' => \DateTime::class,
                'arguments' => [
                    'dateTimeString' => '1980-02-19 12:15:00',
                ]
            ],
            'x-service' => [
                'class' => Fixtures\ClassX::class,
                'arguments' => [
                    'y-service',
                    'z-service',
                ],
            ],
            'y-service' => [
                'class' => Fixtures\ClassY::class,
                'arguments' => [
                    'a-date-service'
                ],
            ],
            'z-service' => [
                'class' => Fixtures\ClassZ::class,
                'arguments' => [
                    'a-date-service'
                ],
            ],
        ];

        $container = new Container($config);
        $actualService = $container->get('x-service');

        $this->assertInstanceOf(Fixtures\ClassX::class, $actualService);
    }

    public function testContainerAllowsTheSameReferenceOnConfigInheritNodes(): void
    {
        $config = [
            'm-service' => [
                'class' => Fixtures\ClassM::class,
                'arguments' => [
                    'n-service'
                ],
                'shared' => false
            ],
            'n-service' => [
                'class' => Fixtures\ClassN::class,
                'arguments' => [
                    DateTime::class
                ],
                'shared' => true
            ],
            'o-service' => [
                'class' => Fixtures\ClassO::class,
                'arguments' => [
                    'xm-service',
                    'xn-service',
                ]
            ],
            'xm-service' => [
                'inherits' => 'm-service',
            ],
            'xn-service' => [
                'class' => Fixtures\ClassN::class,
                'arguments' => [
                    DateTime::class
                ],
                'inherits' => 'm-service'  // <--- inherits from M but keeps what we add here
            ]
        ];

        $container = new Container($config);
        $actualService = $container->get('o-service');

        $this->assertInstanceOf(Fixtures\ClassO::class, $actualService);
    }
}
