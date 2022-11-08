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

use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;
use WorstPractice\Component\DependencyInjection\ConfigModel\ConfigItem;

/**
 * @implements IteratorAggregate<string, ConfigItem>
 */
class ConfigModel implements IteratorAggregate
{
    /** @var array<string, ConfigItem> $items */
    private array $items = [];

    public function add(string $id, ConfigItem $item): void
    {
        if (isset($this->items[$id])) {
            throw new InvalidArgumentException(
                sprintf(Error::ERROR_CONFIG_ALREADY_EXISTS->getMessageTemplate(), $id),
                Error::ERROR_CONFIG_ALREADY_EXISTS->getCode()
            );
        }
        $this->items[$id] = $item;
    }

    public function get(string $id): ConfigItem
    {
        return $this->items[$id] ?? throw new OutOfBoundsException(
            sprintf(Error::ERROR_RECORD_NOT_FOUND->getMessageTemplate(), $id),
            Error::ERROR_RECORD_NOT_FOUND->getCode()
        );
    }

    public function update(string $id, ConfigItem $item): void
    {
        if (!isset($this->items[$id])) {
            throw new InvalidArgumentException(
                sprintf(Error::ERROR_CLASS_NOT_FOUND->getMessageTemplate(), $id),
                Error::ERROR_CLASS_NOT_FOUND->getCode()
            );
        }
        $this->items[$id] = $item;
    }

    final public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
