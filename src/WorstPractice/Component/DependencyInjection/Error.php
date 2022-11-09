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

enum Error
{
    case ERROR_CLASS_NOT_FOUND;
    case ERROR_CLASS_NOT_INSTANTIABLE;
    case ERROR_UNKNOWN_METHOD_CALL;
    case ERROR_SERVICE_ALREADY_INITIALIZED;
    case ERROR_SERVICE_NOT_FOUND;
    case ERROR_CONFIG_NOT_FOUND;
    case ERROR_CONFIG_ALREADY_EXISTS;
    case ERROR_SELF_REFERENCE;
    case ERROR_REFERENCE_LOOP;
    case ERROR_INHERITANCE_LOOP;
    case ERROR_RECORD_NOT_FOUND;
    case ERROR_JSON_ENCODE_OR_DECODE;
    case ERROR_METHOD_CANNOT_BE_CALLED;

    public function getCode(): int
    {
        return match ($this) {
            self::ERROR_CLASS_NOT_FOUND => 1000,
            self::ERROR_CLASS_NOT_INSTANTIABLE => 1001,
            self::ERROR_UNKNOWN_METHOD_CALL => 1002,
            self::ERROR_SERVICE_ALREADY_INITIALIZED => 1003,
            self::ERROR_SERVICE_NOT_FOUND => 1004,
            self::ERROR_CONFIG_NOT_FOUND => 1005,
            self::ERROR_CONFIG_ALREADY_EXISTS => 1006,
            self::ERROR_SELF_REFERENCE => 1007,
            self::ERROR_REFERENCE_LOOP => 1008,
            self::ERROR_INHERITANCE_LOOP => 1009,
            self::ERROR_RECORD_NOT_FOUND => 1010,
            self::ERROR_JSON_ENCODE_OR_DECODE => 1011,
            self::ERROR_METHOD_CANNOT_BE_CALLED => 1012,
        };
    }

    public function getMessageTemplate(): string
    {
        return match ($this) {
            self::ERROR_CLASS_NOT_FOUND => 'Class "%s" not found.',
            self::ERROR_CLASS_NOT_INSTANTIABLE => 'The given service (%s) is not an instantiable class.',
            self::ERROR_UNKNOWN_METHOD_CALL => 'The method "%s::%s" does not exist or not public.',
            self::ERROR_SERVICE_ALREADY_INITIALIZED => 'Another service with this identifier (%s) is already exists.',
            self::ERROR_SERVICE_NOT_FOUND => 'The given service (%s) is not defined service or class name.',
            self::ERROR_CONFIG_NOT_FOUND => 'The given identifier (%s) not found in configuration',
            self::ERROR_CONFIG_ALREADY_EXISTS => 'The given identifier (%s) already exists',
            self::ERROR_SELF_REFERENCE => 'Self referencing is not allowed: %s',
            self::ERROR_REFERENCE_LOOP => 'Reference loop detected! Reference chain: %s',
            self::ERROR_INHERITANCE_LOOP => 'Inheritance loop detected for service: %s',
            self::ERROR_RECORD_NOT_FOUND => 'Record "%s" not found in the configuration.',
            self::ERROR_JSON_ENCODE_OR_DECODE => 'Could not apply JSON function on the dataset.',
            self::ERROR_METHOD_CANNOT_BE_CALLED => 'Cannot call method "%s" on "%s" instance.',
        };
    }
}
