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

use DateTime;

class ClassN
{
    private DateTime $date;
    private string $data;

    public function __construct(DateTime $date, string $data = '', array $options = [])
    {
        $this->date = $date;
    }
}

