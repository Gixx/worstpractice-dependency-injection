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

interface ConfigParserInterface
{
    public function parse(mixed $config): ConfigModel;

    /**
     * @param array<int|string, mixed> $data
     * @return ArgumentItemCollection
     */
    public function buildArgumentCollection(array $data): ArgumentItemCollection;

    /**
     * @param array<int, array{0: string, 1: null|array<int|string, mixed>}> $data
     * @return CallableItemCollection
     */
    public function buildCallCollection(array $data): CallableItemCollection;
}
