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

use OutOfBoundsException;
use RuntimeException;
use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\CallableItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\ConfigItem;

class ServiceLibrary
{
    private ConfigModel $configModel;
    /**
     * @var array<string, ServiceBook>
     */
    private array $library = [];
    /**
     * @var array<string, string>
     */
    private array $inheritanceLoopDetector = [];

    public function __construct(private readonly ConfigParserInterface $configParser)
    {
    }

    public function build(mixed $config): void
    {
        $this->configModel = $this->configParser->parse($config);

        foreach ($this->configModel as $id => $configItem) {
            if (!$configItem instanceof ConfigItem) {
                continue;
            }

            $resolvedConfigItem = $this->resolveInheritance($configItem);
            $this->configModel->update($id, $resolvedConfigItem);

            $this->set(
                id: $resolvedConfigItem->id,
                class: $resolvedConfigItem->class ?? $resolvedConfigItem->id,
                arguments: $resolvedConfigItem->arguments ?? new ArgumentItemCollection(),
                calls: $resolvedConfigItem->calls ?? new CallableItemCollection(),
                shared: $resolvedConfigItem->isShared ?? true
            );
        }
    }

    public function set(
        string $id,
        ?string $class = null,
        ArgumentItemCollection $arguments = new ArgumentItemCollection(),
        CallableItemCollection $calls = new CallableItemCollection(),
        bool $shared = true
    ): void {
        $this->library[$id] = new ServiceBook(
            class: $class ?? $id,
            arguments: $arguments,
            calls: $calls,
            shared: $shared
        );
    }

    public function get(string $id): ServiceBook
    {
        return $this->library[$id] ?? throw new OutOfBoundsException(
            sprintf(Error::ERROR_SERVICE_NOT_FOUND->getMessageTemplate(), $id),
            Error::ERROR_SERVICE_NOT_FOUND->getCode()
        );
    }

    public function has(string $id): bool
    {
        return isset($this->library[$id]);
    }

    private function resolveInheritance(ConfigItem $configItem): ConfigItem
    {
        if (empty($configItem->inherits)) {
            return $configItem;
        }

        $this->checkForInheritanceLoop($configItem->inherits, $configItem->id);
        $parentConfigItem = $this->resolveInheritance($this->configModel->get($configItem->inherits));

        // Add the missing data from parent when locally not present
        $id = $configItem->id;
        $class = $configItem->class ?? $parentConfigItem->class;
        $inherits = null;
        $arguments = $configItem->arguments ?? $parentConfigItem->arguments;
        $calls = $configItem->calls ?? $parentConfigItem->calls;
        $isShared = $configItem->isShared ?? $parentConfigItem->isShared;

        // If the identifier is a valid class name, the inherited class name should be overwritten.
        if (class_exists($id)) {
            $class = $id;
        }

        return new ConfigItem(
            id: $id,
            class: $class,
            inherits: $inherits,
            arguments: $arguments,
            calls: $calls,
            isShared: $isShared
        );
    }

    private function checkForInheritanceLoop(string $parentIdentifier, string $id): void
    {
        if ($parentIdentifier === $id) {
            throw new RuntimeException(
                sprintf(Error::ERROR_SELF_REFERENCE->getMessageTemplate(), $id),
                Error::ERROR_SELF_REFERENCE->getCode()
            );
        }

        if (isset($this->inheritanceLoopDetector[$id])) {
            throw new RuntimeException(
                sprintf(Error::ERROR_INHERITANCE_LOOP->getMessageTemplate(), $id),
                Error::ERROR_INHERITANCE_LOOP->getCode()
            );
        }

        // Save ID to remember that this config item inherits, so later we can check
        $this->inheritanceLoopDetector[$id] = $id;
    }
}
