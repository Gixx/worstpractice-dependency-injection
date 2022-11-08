<?php

/**
 * Worst Practice DI Component
 *
 * PHP version 8.2
 *
 * @copyright 2022 Worst Practice
 * @license   https://opensource.org/licenses/MIT The MIT License (MIT)
 *
 * @link http://www.worstpractice.dev
 */

declare(strict_types=1);

namespace WorstPracticeTest\Component\DependencyInjection;

use WorstPractice\Component\DependencyInjection\ConfigParser\ArrayParser;
use WorstPractice\Component\DependencyInjection\Error;
use WorstPractice\Component\DependencyInjection\ServiceLibrary;
use DateTime;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class ServiceLibraryTest extends TestCase
{
    /**
     * Tests if the library can be built from the config.
     */
    public function testLibraryBuild(): void
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

        $serviceLibrary = new ServiceLibrary(new ArrayParser());

        $serviceLibrary->build($config);
        $this->assertTrue($serviceLibrary->has('SharedDateService'));
        $this->assertTrue($serviceLibrary->has('NotSharedDateService'));
        $this->assertTrue($serviceLibrary->has(DateTime::class));
    }

    /**
     * Tests if the library can add a service config only by the ID.
     */
    public function testLibraryCreateBookInstanceFromIdOnly(): void
    {
        $serviceLibrary = new ServiceLibrary(new ArrayParser());
        $this->assertFalse($serviceLibrary->has('AdditionalService'));
        $serviceLibrary->set('AdditionalService');
        $this->assertTrue($serviceLibrary->has('AdditionalService'));
    }

    /**
     * Tests if the library can add a service config from config data.
     */
    public function testLibraryCreateBookInstanceFromIdAndConfig(): void
    {
        $configParser = new ArrayParser();
        $serviceLibrary = new ServiceLibrary($configParser);
        $this->assertFalse($serviceLibrary->has('AdditionalService'));
        $serviceLibrary->set(
            id: 'AdditionalService',
            class: '\\Namespace\\To\\Existing\\Class',
            arguments: $configParser->buildArgumentCollection(['name' => 'Joe', 'age' => 42, true]),
            calls: $configParser->buildCallCollection([
                ['setAddress', ['POBox' => 80809, 'City' => 'München']]
            ]),
            shared: false
        );
        $this->assertTrue($serviceLibrary->has('AdditionalService'));
    }

    /**
     * Tests if the library will throw an exception when a non-registered service ID is referenced.
     */
    public function testLibraryGetErrorForNonRegisteredService(): void
    {
        $serviceLibrary = new ServiceLibrary(new ArrayParser());
        $this->assertFalse($serviceLibrary->has('AdditionalService'));

        $this->expectExceptionCode(Error::ERROR_SERVICE_NOT_FOUND->getCode());
        $serviceLibrary->get('AdditionalService');
    }
}