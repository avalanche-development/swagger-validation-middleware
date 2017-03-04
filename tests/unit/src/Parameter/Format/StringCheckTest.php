<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class StringCheckTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a string
     */
    public function testCheckThrowsExceptionIfNotAString()
    {
        $mockParam = [
            'value' => 1234,
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckIgnoresFormatIfNotDefined()
    {
        $mockParam = [
            'value' => 'some string',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a byte
     */
    public function testCheckThrowsExceptionIfNotAByte()
    {
        $mockParam = [
            'value' => 'some string',
            'format' => 'byte',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckContinuesIfByteIsByte()
    {
        $mockParam = [
            'value' => 'someString=',
            'format' => 'byte',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a binary
     */
    public function testCheckThrowsExceptionIfNotABinary()
    {
        $mockParam = [
            'value' => 'some string',
            'format' => 'binary',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckContinuesIfBinaryIsBinary()
    {
        $mockParam = [
            'value' => '10101010',
            'format' => 'binary',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a date
     */
    public function testCheckThrowsExceptionIfNotADate()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'value' => 'some string',
            'format' => 'date',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckContinuesIfDateIsADate()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'value' => 'some string',
            'format' => 'date',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a datetime
     */
    public function testCheckThrowsExceptionIfNotADatetime()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'value' => 'some string',
            'format' => 'datetime',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckContinuesIfDatetimeIsADatetime()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'value' => 'some string',
            'format' => 'datetime',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();

        $stringCheck->check($mockParam);
    }

    public function testCheckCallsToCheckLength()
    {
        $mockParam = [
            'value' => 'some string',
            'format' => 'some format',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();
        $stringCheck->expects($this->once())
            ->method('checkLength')
            ->with($mockParam);

        $stringCheck->check($mockParam);
    }

    public function testCheckCallsToCheckPattern()
    {
        $mockParam = [
            'value' => 'some string',
            'format' => 'some format',
        ];

        $stringCheck = $this->getMockBuilder(StringCheck::class)
            ->setMethods([
                'checkLength',
                'checkPattern',
            ])
            ->getMock();
        $stringCheck->expects($this->once())
            ->method('checkPattern')
            ->with($mockParam);

        $stringCheck->check($mockParam);
    }

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
