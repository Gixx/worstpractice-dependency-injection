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

namespace WorstPracticeTest\Component\DependencyInjection;

use WorstPractice\Component\DependencyInjection\ConfigModel;
use WorstPractice\Component\DependencyInjection\ConfigModel\ArgumentItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\CallableItemCollection;
use WorstPractice\Component\DependencyInjection\ConfigModel\ConfigItem;
use WorstPractice\Component\DependencyInjection\ConfigParser\ArrayParser;
use WorstPractice\Component\DependencyInjection\Error;
use WorstPractice\Component\DependencyInjection\ServiceLibrary;
use DateTime;
use PHPUnit\Framework\TestCase;

class ConfigModelTest extends TestCase
{
    public function testConfigModelCanAddConfigItem(): void
    {
        $configItem = new ConfigItem(
            id: 'test',
            class: DateTime::class,
            inherits: null,
            arguments: new ArgumentItemCollection(),
            calls: new CallableItemCollection(),
            isShared: true,
        );

        $configModel = new ConfigModel();
        $configModel->add('test', $configItem);
        $result = $configModel->get('test');

        $this->assertSame($result, $configItem);
    }

    public function testConfigModelFailWhenAddExistingConfigItem(): void
    {
        $configItem = new ConfigItem(
            id: 'test',
            class: DateTime::class,
            inherits: null,
            arguments: new ArgumentItemCollection(),
            calls: new CallableItemCollection(),
            isShared: true,
        );

        $configModel = new ConfigModel();
        $configModel->add('test', $configItem);
        $this->expectExceptionCode(Error::ERROR_CONFIG_ALREADY_EXISTS->getCode());
        $configModel->add('test', $configItem);
    }

    public function testConfigModelFailWhenUpdateNotExistingConfigItem(): void
    {
        $configItem = new ConfigItem(
            id: 'test',
            class: DateTime::class,
            inherits: null,
            arguments: new ArgumentItemCollection(),
            calls: new CallableItemCollection(),
            isShared: true,
        );

        $configModel = new ConfigModel();
        $this->expectExceptionCode(Error::ERROR_CLASS_NOT_FOUND->getCode());
        $configModel->update('test', $configItem);
    }
}