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

namespace WorstPracticeTest\Fixtures;

class ClassM
{
    private object $objectReference;

    public function __construct(ClassN $classN)
    {
        $this->objectReference = $classN;
    }

    public function changeInjectedClass(object $otherObjectReference): void
    {
        $this->objectReference = $otherObjectReference;
    }
}
