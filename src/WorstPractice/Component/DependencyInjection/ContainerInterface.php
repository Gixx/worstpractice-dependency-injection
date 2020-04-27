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

namespace WorstPractice\Component\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use RuntimeException;

/**
 * Interface ContainerInterface
 * @package WorstPractice\Component\DependencyInjection
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Register the service object instance.
     *
     * @param string $identifier
     * @param object $serviceInstance
     * @param bool   $isShared
     * @throws RuntimeException
     */
    public function set(string $identifier, object $serviceInstance, bool $isShared = true): void;
}
