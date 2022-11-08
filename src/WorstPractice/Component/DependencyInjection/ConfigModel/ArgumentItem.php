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

namespace WorstPractice\Component\DependencyInjection\ConfigModel;

readonly class ArgumentItem
{
    public function __construct(
        public int $index,
        public bool $isReference,
        public string $value,
        public string $type,
    ) {
    }
}
