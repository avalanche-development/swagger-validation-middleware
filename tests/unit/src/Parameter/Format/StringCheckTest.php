<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class StringCheckTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds maxLength
     */
    public function testCheckLengthFailsIfValueIsTooLong()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0123456789',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckLength = $reflectedStringCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckLength->invokeArgs($stringCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds minLength
     */
    public function testCheckLengthFailsIfValueIsTooShort()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckLength = $reflectedStringCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckLength->invokeArgs($stringCheck, [ $mockParam ]);
    }

    public function testCheckLengthPassesIfValueIsJustRight()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0123',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckLength = $reflectedStringCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckLength->invokeArgs($stringCheck, [ $mockParam ]);
    }

    public function testCheckPatternBailsIfPatternIsEmpty()
    {
        $mockParam = [
            'value' => 'happy',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckPattern = $reflectedStringCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckPattern->invokeArgs($stringCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value does not match pattern
     */
    public function testCheckPatternFailsIfValueDoesNotMatchPattern()
    {
        $mockParam = [
            'pattern' => '^[a-z]$',
            'value' => '1234',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckPattern = $reflectedStringCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckPattern->invokeArgs($stringCheck, [ $mockParam ]);
    }

    public function testCheckPatternPassesIfValueMatchesPattern()
    {
        $mockParam = [
            'pattern' => '^[0-1]{4}$',
            'value' => '1010',
        ];

        $reflectedStringCheck = new ReflectionClass(StringCheck::class);
        $reflectedCheckPattern = $reflectedStringCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckPattern->invokeArgs($stringCheck, [ $mockParam ]);
    }
}
