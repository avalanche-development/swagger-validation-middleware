<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class NumberCheckTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a number
     */
    public function testCheckThrowsExceptionIfNotANumber()
    {
        $mockParam = [
            'value' => 'some string',
        ];

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->setMethods([ 'checkRange' ])
            ->getMock();

        $numberCheck->check($mockParam);
    }

    public function testCheckCallsToRangeCheckIfOkay()
    {
        $mockParam = [
            'value' => 512.123,
        ];

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->setMethods([ 'checkRange' ])
            ->getMock();

        $numberCheck->expects($this->once())
            ->method('checkRange')
            ->with($mockParam);

        $numberCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds maximum
     */
    public function testCheckRangeFailsIfValueIsExceedsMaximum()
    {
        $mockParam = [
            'maximum' => 5,
            'minimum' => 2,
            'value' => 6,
        ];

        $reflectedNumberCheck = new ReflectionClass(NumberCheck::class);
        $reflectedCheckRange = $reflectedNumberCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->getMock();

        $reflectedCheckRange->invokeArgs($numberCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds exclusiveMaximum
     */
    public function testCheckRangeFailsIfValueIsExceedsExclusiveMaximum()
    {
        $mockParam = [
            'exclusiveMaximum' => 5,
            'value' => 5,
        ];

        $reflectedNumberCheck = new ReflectionClass(NumberCheck::class);
        $reflectedCheckRange = $reflectedNumberCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->getMock();

        $reflectedCheckRange->invokeArgs($numberCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds minimum
     */
    public function testCheckRangeFailsIfValueIsExceedsMinimum()
    {
        $mockParam = [
            'minimum' => 5,
            'minimum' => 2,
            'value' => 1,
        ];

        $reflectedNumberCheck = new ReflectionClass(NumberCheck::class);
        $reflectedCheckRange = $reflectedNumberCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->getMock();

        $reflectedCheckRange->invokeArgs($numberCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds exclusiveMinimum
     */
    public function testCheckRangeFailsIfValueIsExceedsExclusiveMinimum()
    {
        $mockParam = [
            'exclusiveMinimum' => 2,
            'value' => 2,
        ];

        $reflectedNumberCheck = new ReflectionClass(NumberCheck::class);
        $reflectedCheckRange = $reflectedNumberCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $numberCheck = $this->getMockBuilder(NumberCheck::class)
            ->getMock();

        $reflectedCheckRange->invokeArgs($numberCheck, [ $mockParam ]);
    }
}
