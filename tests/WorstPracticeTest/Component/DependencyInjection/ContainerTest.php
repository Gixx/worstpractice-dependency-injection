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
}
