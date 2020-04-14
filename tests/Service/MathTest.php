<?php

declare(strict_types=1);

namespace Bank\Commission\Tests\Service;

use PHPUnit\Framework\TestCase;
use Bank\Commission\Service\Math;

class MathTest extends TestCase
{
    /**
     * @var Math
     */
    private $math;

    public function setUp()
    {
        $this->math = new Math(2);
    }

    /**
     * @param string $leftOperand
     * @param string $rightOperand
     * @param string $expectation
     *
     * @dataProvider dataProviderForAddTesting
     */
    public function testAdd(string $leftOperand, string $rightOperand, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->math->add($leftOperand, $rightOperand)
        );
    }

    public function dataProviderForAddTesting(): array
    {
        return [
            'add 2 natural numbers' => ['1', '2', '3'],
            'add negative number to a positive' => ['-1', '2', '1'],
            'add natural number to a float' => ['1', '1.05123', '2.05'],
        ];
    }

//    public function testPushAndPop()
//    {
//        $stack = [];
//        $this->assertSame(0, count($stack));
//
//        array_push($stack, 'foo');
//        $this->assertSame('foo', $stack[count($stack)-1]);
//        $this->assertSame(1, count($stack));
//
//        $this->assertSame('foo', array_pop($stack));
//        $this->assertSame(0, count($stack));
//    }
}
