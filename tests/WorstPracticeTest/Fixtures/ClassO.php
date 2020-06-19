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
}
