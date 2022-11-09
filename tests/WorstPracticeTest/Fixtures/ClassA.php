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

class ClassA
{
    private ClassB $param;

    public function __construct(ClassB $param)
    {
        $this->param = $param;
    }

    public function getParam(): ClassB
    {
        return $this->param;
    }
}
