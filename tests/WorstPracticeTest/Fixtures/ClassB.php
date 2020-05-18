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

class ClassB
{
    private ClassC $param;

    public function __construct(ClassC $param)
    {
        $this->param = $param;
    }

    public function getParam(): ClassC
    {
        return $this->param;
    }
}
