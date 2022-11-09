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

class ClassO
{
    private ClassM $classM;
    private ClassN $classN;

    public function __construct(ClassM $classM, ClassN $classN)
    {
        $this->classM = $classM;
        $this->classN = $classN;
    }

    public function someMethod(int $param1, ClassX $param2): int
    {
        return $param1 + $param2->id;
    }
}
