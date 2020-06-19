<?php

/**
 * Worst Practice DI Component
 *
 * PHP version 7.4
 *
 * @copyright 2020 Worst Practice
 * @license   https://opensource.org/licenses/MIT The MIT License (MIT)
 *
 * @link http://www.worstpractice.dev
 */

declare(strict_types=1);

namespace WorstPractice\Component\DependencyInjection;

use OutOfBoundsException;
use RuntimeException;

/**
 * Class Container
 * @package WorstPractice\Component\DependencyInjection
 */
class Container implements ContainerInterface
{
    private const SERVICE_CLASS = 'class';
    private const SERVICE_ARGUMENTS = 'arguments';
    private const SERVICE_METHOD_CALL = 'calls';
    private const SERVICE_SHARE = 'shared';
    private const SERVICE_INHERIT = 'inherits';
    private const SERVICE_INITIALIZED = 'initialized';

    /**
     * @var array The full raw configuration data
     */
    private array $configuration;
    /**
     * @var array The configuration data with resolved inherited configuration.
     */
    private array $serviceConfiguration;
    /**
     * @var array The instantiation-ready library with all necessary data.
     */
    private array $serviceLibrary;
    /**
     * @var array The instantiated services.
     */
    private array $serviceContainer;
    /**
     * @var array An array to detect reference loops.
     */
    private array $referenceLoopDetector = [];
    /**
     * @var array An array to detect inheritance loops.
     */
    private array $inheritanceLoopDetector = [];

    /**
     * Container constructor.
     *
     * @param array $configuration
     * @example There's no validation on the configuration structure, but it will work only when it's built
     *          in the following way:
     *
     *          $configuration = [
     *              'someAlias' => [
     *                  'class' => \Namespace\To\Class::class,
     *                  'arguments' => [
     *                      '\ArrayObject',
     *                      '\DateTime',
     *                      'literalArgument' => 1024
     *                  ],
     *                  'calls' => [
     *                      ['someMethod', ['aLiteralArgument1' => 3242, 'anotherLiteralArgument' => 'Hello World!']],
     *                      ['someOtherMethod', ['otherAlias']],
     *                  ],
     *                  'shared' => true,
     *              ],
     *              'otherAlias' => [
     *                  'class' => \Namespace\To\OtherClass::class,
     *                  'arguments' => [
     *                      'aBooleanParameter' => false
     *                  ]
     *                  'shared' => false,
     *              ],
     *              \Namespace\To\OtherSpecification::class => [
     *                  'inherits' => 'otherAlias',
     *                  'shared' => true,
     *              ],
     *              \Namespace\To\Service\Interface::class => [
     *                  'class' => \Namespace\To\Service\Implementation::class
     *                  'arguments' => [
     *                      'someAlias',
     *                      'aLiteralParameter' => 'someAlias'
     *                  ]
     *              ]
     *          ];
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Returns true if the given service is registered.
     *
     * @param  string $identifier
     * @return bool
     */
    public function has($identifier): bool
    {
        return $this->isServiceRegisteredIntoContainer($identifier)
            || $this->isServiceRegisteredIntoLibrary($identifier)
            || $this->isServiceRegistrableIntoLibrary($identifier);
    }

    /**
     * Gets a service instance.
     *
     * @param  string $identifier
     * @return object
     */
    public function get($identifier): object
    {
        return $this->getFromContainer((string) $identifier);
    }

    /**
     * @param string $identifier
     * @throws OutOfBoundsException
     * @throws RuntimeException
     * @return object
     */
    private function getFromContainer(string $identifier): object
    {
        if (in_array($identifier, $this->referenceLoopDetector, true)) {
            throw new RuntimeException(
                sprintf('Reference loop detected! Reference chain: %s', implode(' -> ', $this->referenceLoopDetector)),
                1000
            );
        }

        $this->referenceLoopDetector[] = $identifier;

        $this->prepareService($identifier);

        // If still not exists, then kill it.
        if (!$this->isServiceRegisteredIntoLibrary($identifier)) {
            throw new OutOfBoundsException(
                sprintf('The given service (%s) is not defined service or class name.', $identifier),
                1001
            );
        }

        // In this point we always have the same element in the last position we added 10 lines earlier.
        array_pop($this->referenceLoopDetector);

        return $this->serviceLibrary[$identifier][self::SERVICE_SHARE]
            ? $this->serviceContainer[$identifier]
            : clone $this->serviceContainer[$identifier];
    }

    /**
     * Before getting a service, check if it is ready and registered into the container.
     *
     * @param string $identifier
     */
    private function prepareService(string $identifier): void
    {
        // Not registered in the library but it's a valid class name, or it's in the raw configuration: register.
        if (
            !$this->isServiceRegisteredIntoLibrary($identifier)
            && $this->isServiceRegistrableIntoLibrary($identifier)
        ) {
            $this->registerServiceToLibrary($identifier);
        }

        // Registered in the library but not in the container, so register it there too.
        if (
            $this->isServiceRegisteredIntoLibrary($identifier)
            && !$this->isServiceRegisteredIntoContainer($identifier)
        ) {
            $this->registerServiceToContainer($identifier);
        }
    }

    /**
     * Register a service object instance into the container.
     *
     * @param  string $identifier
     * @param  object $serviceInstance
     * @param  bool   $isShared
     * @throws RuntimeException
     */
    public function set(string $identifier, object $serviceInstance, bool $isShared = true): void
    {
        // Check if the service is initialized already.
        if ($this->isServiceInitialized($identifier)) {
            throw new RuntimeException(
                sprintf('Another service with this identifier (%s) is already initialized.', $identifier),
                1003
            );
        }

        // Register service.
        $this->serviceContainer[$identifier] = $serviceInstance;

        // Overwrite any previous settings.
        $this->serviceLibrary[$identifier] = [
            self::SERVICE_INITIALIZED => true,
            self::SERVICE_CLASS => get_class($serviceInstance),
            self::SERVICE_ARGUMENTS => [],
            self::SERVICE_METHOD_CALL => [],
            self::SERVICE_SHARE => $isShared,
        ];
    }

    /**
     * Checks if the service name is a valid class, or it's in the raw configuration.
     *
     * @param string $identifier
     * @return bool
     */
    private function isServiceRegistrableIntoLibrary(string $identifier): bool
    {
        return class_exists($identifier) || isset($this->configuration[$identifier]);
    }

    /**
     * Checks if the service has been already registered into the library
     *
     * @param string $identifier
     * @return bool
     */
    private function isServiceRegisteredIntoLibrary(string $identifier): bool
    {
        return isset($this->serviceLibrary[$identifier]);
    }

    /**
     * Checks if the service has been already registered into the container
     *
     * @param string $identifier
     * @return bool
     */
    private function isServiceRegisteredIntoContainer(string $identifier): bool
    {
        return isset($this->serviceContainer[$identifier]);
    }

    /**
     * Checks if the service has been already initialized.
     *
     * @param  string $identifier
     * @return bool
     */
    private function isServiceInitialized(string $identifier): bool
    {
        return $this->serviceLibrary[$identifier][self::SERVICE_INITIALIZED] ?? false;
    }

    /**
     * Register the service.
     *
     * @param  string $identifier
     */
    private function registerServiceToLibrary(string $identifier): void
    {
        $serviceConfiguration = $this->getServiceConfiguration($identifier);
        $className = $serviceConfiguration[self::SERVICE_CLASS] ?? $identifier;

        if (!class_exists($className)) {
            throw new RuntimeException(
                sprintf('The resolved class "%s" cannot be found.', $className),
                1006
            );
        }

        $this->serviceLibrary[$identifier] = [
            self::SERVICE_INITIALIZED => false,
            self::SERVICE_CLASS => $className,
            self::SERVICE_ARGUMENTS => $serviceConfiguration[self::SERVICE_ARGUMENTS] ?? [],
            self::SERVICE_METHOD_CALL => $serviceConfiguration[self::SERVICE_METHOD_CALL] ?? [],
            self::SERVICE_SHARE => $serviceConfiguration[self::SERVICE_SHARE] ?? true,
        ];
    }

    /**
     * Retrieves configuration for a service.
     *
     * @param  string $identifier
     * @return array
     */
    private function getServiceConfiguration(string $identifier): array
    {
        if (isset($this->serviceConfiguration[$identifier])) {
            return $this->serviceConfiguration[$identifier];
        }

        // In case of classes without config, we provide an empty array
        $configuration = $this->configuration[$identifier] ?? [];

        // Resolve inheritance.
        $this->resolveInheritance($configuration, $identifier);

        // Save the configuration.
        $this->serviceConfiguration[$identifier] = $configuration;

        return $configuration;
    }

    /**
     * Resolves the config inheritance.
     *
     * @param array  $configuration
     * @param string $identifier
     * @throws RuntimeException
     */
    private function resolveInheritance(array &$configuration, string $identifier): void
    {
        if (!isset($configuration[self::SERVICE_INHERIT])) {
            return;
        }

        $this->checkForInheritanceLoop($configuration[self::SERVICE_INHERIT], $identifier);

        $this->inheritanceLoopDetector[] = $identifier;
        $parentConfiguration = $this->getServiceConfiguration($configuration[self::SERVICE_INHERIT]);

        // not needed any more
        unset($configuration[self::SERVICE_INHERIT]);

        // Overwrite the parent service's config with the current service's config
        foreach ($configuration as $key => $value) {
            $parentConfiguration[$key] = $value;
        }

        // If the class name is not explicitly defined but the identifier is a valid class name,
        // the inherited class name should be overwritten.
        if (!isset($configuration[self::SERVICE_CLASS]) && class_exists($identifier)) {
            $parentConfiguration[self::SERVICE_CLASS] = $identifier;
        }

        $configuration = $parentConfiguration;
    }

    /**
     * @param string $parentIdentifier
     * @param string $identifier
     * @throws RuntimeException
     */
    private function checkForInheritanceLoop(string $parentIdentifier, string $identifier): void
    {
        if ($parentIdentifier === $identifier) {
            throw new RuntimeException(
                sprintf('Self referencing is not allowed: %s', $identifier),
                1004
            );
        }

        if (in_array($identifier, $this->inheritanceLoopDetector, true)) {
            throw new RuntimeException(
                sprintf('Inheritance loop detected for service: %s', $identifier),
                1005
            );
        }
    }

    /**
     * Registers the service into the container AKA create the instance.
     *
     * @param string $identifier
     * @throws RuntimeException
     */
    private function registerServiceToContainer(string $identifier): void
    {
        // Check arguments.
        $argumentList = $this->setArgumentListReferences($this->serviceLibrary[$identifier][self::SERVICE_ARGUMENTS]);

        // Create new instance.
        $className = $this->serviceLibrary[$identifier][self::SERVICE_CLASS];
        $serviceInstance = new $className(...$argumentList);

        // Perform post init method calls.
        foreach ($this->serviceLibrary[$identifier][self::SERVICE_METHOD_CALL] as $methodCallList) {
            $method = $methodCallList[0];

            if (!method_exists($serviceInstance, $method)) {
                throw new RuntimeException(
                    sprintf('The method "%s::%s" does not exist or not public.', $className, $method),
                    1007
                );
            }

            $methodArgumentList = $this->setArgumentListReferences($methodCallList[1] ?? []);
            $serviceInstance->$method(...$methodArgumentList);
        }

        // Register service.
        $this->serviceContainer[$identifier] = $serviceInstance;

        // Mark as initialized.
        $this->serviceLibrary[$identifier][self::SERVICE_INITIALIZED] = true;
    }

    /**
     * Tries to identify reference services in the argument list.
     *
     * @param  array $argumentList
     * @return array
     */
    private function setArgumentListReferences(array $argumentList): array
    {
        $resolvedArgumentList = [];

        foreach ($argumentList as $key => $value) {
            // Associative array keys marks literal values
            if (is_numeric($key)) {
                $value = $this->getFromContainer($value);
            }

            $resolvedArgumentList[] = $value;
        }

        return $resolvedArgumentList;
    }
}
