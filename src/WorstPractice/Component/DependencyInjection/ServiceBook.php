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

namespace WorstPractice\Component\DependencyInjection;

use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\CallableItemCollection;

/**
 * @property string $class
 */
readonly class ServiceBook
{
    public function __construct(
        public string $class,
        public ArgumentItemCollection $arguments,
        public CallableItemCollection $calls,
        public bool $shared
    ) {
    }
}
