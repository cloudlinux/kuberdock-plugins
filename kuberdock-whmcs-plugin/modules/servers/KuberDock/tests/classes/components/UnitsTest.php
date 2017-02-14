<?php

namespace tests\components;


use \tests\TestCase;
use components\Units;

class UnitsTest extends TestCase
{
    /**
     * @dataProvider unitProvider
     */
    public function testMethods($method, $const, $expected)
    {
        $unit = Units::$method();

        $this->assertInternalType('string', $unit);
        $this->assertEquals($const, $unit);
        $this->assertEquals($expected, $unit);
    }

    public function unitProvider()
    {
        return [
            ['getIPUnits', Units::IP, 'IP'],
            ['getPSUnits', Units::PS, 'GB'],
            ['getCPUUnits', Units::CPU, 'Cores'],
            ['getMemoryUnits', Units::MEMORY, 'MB'],
            ['getHDDUnits', Units::HDD, 'GB'],
            ['getTrafficUnits', Units::TRAFFIC, 'GB'],
        ];
    }
}