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

class ClassX
{
    private ClassY $classY;
    private ClassZ $classZ;
    public int $id = 1234;

    public function __construct(ClassY $classY, ClassZ $classZ)
    {
        $this->classY = $classY;
        $this->classZ = $classZ;
    }
}
