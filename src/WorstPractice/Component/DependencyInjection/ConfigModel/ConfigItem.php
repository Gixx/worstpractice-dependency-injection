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

readonly class ConfigItem
{
    public function __construct(
        public string $id,
        public ?string $class,
        public ?string $inherits,
        public ?ArgumentItemCollection $arguments,
        public ?CallableItemCollection $calls,
        public ?bool $isShared,
    ) {
    }
}
