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

namespace WorstPractice\Component\DependencyInjection\ConfigParser;

use WorstPractice\Component\DependencyInjection\ConfigModel;
use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItem;
use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\CallableItem;
use WorstPractice\Component\DependencyInjection\ConfigModel\CallableItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigParserInterface;

abstract class AbstractParser implements ConfigParserInterface
{
    abstract protected function convertConfigDataToConfigModel(mixed $config): ConfigModel;

    final public function parse(mixed $config): ConfigModel
    {
        return $this->convertConfigDataToConfigModel($config);
    }

    /**
     * @param array<int|string, mixed> $data
     * @return ArgumentItemCollection
     */
    final public function buildArgumentCollection(array $data): ArgumentItemCollection
    {
        $argumentItemCollection = new ArgumentItemCollection();
        $numericIndex = 0;

        foreach ($data as $key => $value) {
            $value = $this->convertNumericStringToNumber($value);

            $argumentItemCollection->add(new ArgumentItem(
                index: $numericIndex++,
                isReference: is_numeric($key),
                value: $this->stringifyValue($value),
                type: $this->getValueType($value)
            ));
        }

        return $argumentItemCollection;
    }

    public function convertNumericStringToNumber(mixed $value): mixed
    {
        if (!is_string($value) || !is_numeric($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_INT) ?: filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public function stringifyValue(mixed $value): string
    {
        return is_object($value) || is_array($value)
            ? (string)json_encode($value)
            : strval($value);
    }

    public function getValueType(mixed $value): string
    {
        return is_object($value) || is_array($value)
            ? 'array' :
            gettype($value);
    }

    /**
     * @param array<int, array{0: string, 1: null|array<int|string, mixed>}> $data
     * @return CallableItemCollection
     */
    final public function buildCallCollection(array $data): CallableItemCollection
    {
        $callableItemCollection = new CallableItemCollection();

        foreach ($data as $callableData) {
            $method = (string) $callableData[0];
            $parameters = $callableData[1] ?? [];

            $callableItemCollection->add(new CallableItem(
                method: $method,
                arguments: $this->buildArgumentCollection((array) $parameters)
            ));
        }

        return $callableItemCollection;
    }
}
