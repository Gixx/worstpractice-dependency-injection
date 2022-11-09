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

class ClassE
{
    private array $params = [];

    public function __construct(
        DateTime $param1,
        string $param2,
        int $param3,
        int $param4,
        float $param5,
        float $param6,
        ?int $param7,
        array $param8,
        bool $param9
    )
    {
        $this->params = [
            $param1,
            $param2,
            $param3,
            $param4,
            $param5,
            $param6,
            $param7,
            $param8,
            $param9,
        ];
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
