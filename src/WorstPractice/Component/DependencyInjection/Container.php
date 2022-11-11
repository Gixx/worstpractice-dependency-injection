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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use RuntimeException;
use Throwable;
use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItemCollection;

class Container implements ContainerInterface
{
    private readonly ServiceLibrary $serviceLibrary;
    /**
     * @var array<string,object> The instantiated services.
     */
    private array $serviceContainer = [];
    /**
     * @var array<int, string> An array to detect reference loops.
     */
    private array $referenceLoopDetector = [];

    public function __construct(ServiceLibrary $serviceLibrary, mixed $configuration)
    {
        $this->serviceLibrary = $serviceLibrary;
        $this->serviceLibrary->build($configuration);
    }

    public function has(string $id): bool
    {
        return $this->isServiceRegisteredIntoContainer($id)
            || $this->isServiceRegisteredIntoLibrary($id)
            || $this->isServiceRegistrableIntoLibrary($id);
    }

    public function get(string $id): object
    {
        if (in_array($id, $this->referenceLoopDetector, true)) {
            throw new RuntimeException(
                sprintf(
                    Error::ERROR_REFERENCE_LOOP->getMessageTemplate(),
                    implode(' -> ', $this->referenceLoopDetector)
                ),
                Error::ERROR_REFERENCE_LOOP->getCode()
            );
        }
        // Save ID into the reference loop detector.
        $this->referenceLoopDetector[] = $id;

        $this->prepareService($id);

        // At this point we always have the same element in the last position, that we added a few lines earlier.
        array_pop($this->referenceLoopDetector);

        return $this->serviceLibrary->get($id)->shared
            ? $this->serviceContainer[$id]
            : clone $this->serviceContainer[$id];
    }

    public function set(string $id, object $serviceInstance, bool $isShared = true): void
    {
        // Check if the service is initialized already.
        if ($this->isServiceRegisteredIntoContainer($id)) {
            throw new RuntimeException(
                sprintf(Error::ERROR_SERVICE_ALREADY_INITIALIZED->getMessageTemplate(), $id),
                Error::ERROR_SERVICE_ALREADY_INITIALIZED->getCode()
            );
        }

        // Register service instance.
        $this->serviceContainer[$id] = $serviceInstance;

        // Load new config to the library / Overwrite any previous settings when exists.
        $this->serviceLibrary->set(
            id: $id,
            class: $serviceInstance::class,
            shared: $isShared
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepareService(string $id): void
    {
        // Not registered in the library, but it's an instantiable class.
        if (
            !$this->isServiceRegisteredIntoLibrary($id)
            && $this->isServiceRegistrableIntoLibrary($id)
        ) {
            $this->set($id, new $id());
        }

        // Registered in the library but not in the container.
        if (
            $this->isServiceRegisteredIntoLibrary($id)
            && !$this->isServiceRegisteredIntoContainer($id)
        ) {
            $this->registerServiceToContainer($id);
        }
    }

    private function isServiceRegistrableIntoLibrary(string $id): bool
    {
        $isInstantiable = false;

        if (class_exists($id)) {
            $reflectionClass = new ReflectionClass($id);
            $isInstantiable = $reflectionClass->isInstantiable()
                && ((int) $reflectionClass->getConstructor()?->getNumberOfRequiredParameters()) === 0;
        }

        return $isInstantiable;
    }

    private function isServiceRegisteredIntoLibrary(string $id): bool
    {
        return $this->serviceLibrary->has($id);
    }

    private function isServiceRegisteredIntoContainer(string $id): bool
    {
        return isset($this->serviceContainer[$id]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function registerServiceToContainer(string $id): void
    {
        // Create service instance.
        $serviceInstance = $this->createServiceInstance($id);
        // Call post-init methods.
        $this->callInstanceMethods($id, $serviceInstance);
        // Save instance to container.
        $this->serviceContainer[$id] = $serviceInstance;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createServiceInstance(string $id): object
    {
        $serviceBook = $this->serviceLibrary->get($id);

        if (!class_exists($serviceBook->class)) {
            throw new RuntimeException(
                sprintf(Error::ERROR_CLASS_NOT_FOUND->getMessageTemplate(), $serviceBook->class),
                Error::ERROR_CLASS_NOT_FOUND->getCode()
            );
        }

        $argumentList = $this->setArgumentListReferences($serviceBook->arguments);

        try {
            return new $serviceBook->class(...$argumentList);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(Error::ERROR_CLASS_NOT_INSTANTIABLE->getMessageTemplate(), $id),
                Error::ERROR_CLASS_NOT_INSTANTIABLE->getCode(),
                $exception
            );
        }
    }

    /**
     * @param ArgumentItemCollection $argumentList
     * @return array<int, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function setArgumentListReferences(ArgumentItemCollection $argumentList): array
    {
        $resolvedArgumentList = [];

        foreach ($argumentList as $argumentItem) {
            $value = $this->setParameterType($argumentItem->value, $argumentItem->type);

            if ($argumentItem->isReference) {
                $value = $this->get($argumentItem->value);
            }

            $resolvedArgumentList[] = $value;
        }

        return $resolvedArgumentList;
    }

    private function setParameterType(string $parameter, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) $parameter,
            'integer' => (int) $parameter,
            'double' => (float) $parameter,
            'array' => json_decode($parameter, associative: true),
            'NULL' => null,
            default => $parameter
        };
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function callInstanceMethods(string $id, object $serviceInstance): void
    {
        $serviceBook = $this->serviceLibrary->get($id);

        foreach ($serviceBook->calls as $callItem) {
            $method = $callItem->method;

            if (!method_exists($serviceInstance, $method)) {
                throw new RuntimeException(
                    sprintf(Error::ERROR_UNKNOWN_METHOD_CALL->getMessageTemplate(), $serviceBook->class, $method),
                    Error::ERROR_UNKNOWN_METHOD_CALL->getCode()
                );
            }

            $argumentList = $this->setArgumentListReferences($callItem->arguments);

            try {
                $serviceInstance->$method(...$argumentList);
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    sprintf(
                        Error::ERROR_METHOD_CANNOT_BE_CALLED->getMessageTemplate(),
                        $method,
                        $serviceInstance::class
                    ),
                    Error::ERROR_METHOD_CANNOT_BE_CALLED->getCode(),
                    $exception
                );
            }
        }
    }
}
