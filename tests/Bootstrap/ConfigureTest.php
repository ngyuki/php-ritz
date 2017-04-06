<?php
namespace Test\Bootstrap;

use PHPUnit\Framework\TestCase;
use ngyuki\Ritz\Bootstrap\Configure;

class ConfigureTest extends TestCase
{
    /**
     * @test
     */
    function init_()
    {
        $configure = new Configure();
        $config = $configure->init(glob(__DIR__ . '/_files/*.php'));

        $expected = [
            'aaa' => 234,
            'bbb' => [1, 3, 5, 2, 4, 6, 0],
            'ccc' => [
                'x' => 1,
                'y' => 9,
                'z' => 2,
            ],
        ];

        self::assertEquals($expected, $config);
    }
}
