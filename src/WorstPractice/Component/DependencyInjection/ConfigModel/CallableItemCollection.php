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

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CallableItem>
 */
class CallableItemCollection implements IteratorAggregate
{
    /** @var array<int, CallableItem> $items */
    private array $items = [];

    public function add(CallableItem $item): void
    {
        $this->items[] = $item;
    }

    final public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
