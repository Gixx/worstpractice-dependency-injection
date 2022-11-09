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

use JsonException;
use ReflectionObject;
use RuntimeException;
use WorstPractice\Component\DependencyInjection\ConfigModel;
use WorstPractice\Component\DependencyInjection\ConfigModel\ConfigItem;
use WorstPractice\Component\DependencyInjection\Error;

class ArrayParser extends AbstractParser
{
    protected function convertConfigDataToConfigModel(mixed $config): ConfigModel
    {
        try {
            $jsonData = json_encode(value: $config, flags: JSON_THROW_ON_ERROR);
            $generalObjectData = json_decode(json: $jsonData, associative: false, flags: JSON_THROW_ON_ERROR);
            return is_object($generalObjectData) ? $this->analyzeObject($generalObjectData) : new ConfigModel();
        } catch (JsonException $exception) {
            throw new RuntimeException(
                Error::ERROR_JSON_ENCODE_OR_DECODE->getMessageTemplate(),
                Error::ERROR_JSON_ENCODE_OR_DECODE->getCode(),
                $exception
            );
        }
    }

    private function analyzeObject(object $object): ConfigModel
    {
        $configModel = new ConfigModel();
        $obj = new ReflectionObject($object);

        $properties = $obj->getProperties();

        foreach ($properties as $property) {
            $id = $property->getName();
            $item = $this->buildConfigItem($id, $object->$id);
            $configModel->add($id, $item);
        }

        return $configModel;
    }

    private function buildConfigItem(string $id, object $object): ConfigItem
    {
        $class = property_exists($object, 'class') ? (string) $object->class : null;
        $inherits = property_exists($object, 'inherits') ? (string) $object->inherits : null;
        $arguments = null;
        $calls = null;
        $shared = !property_exists($object, 'shared') || $object->shared;

        if (property_exists($object, 'arguments')) {
            $arguments = $this->buildArgumentCollection((array) $object->arguments);
        }

        if (property_exists($object, 'calls')) {
            $calls = $this->buildCallCollection((array) $object->calls);
        }

        return new ConfigItem(
            id: $id,
            class: $class,
            inherits: $inherits,
            arguments: $arguments,
            calls: $calls,
            isShared: $shared
        );
    }
}
