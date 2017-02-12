<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use PHPUnit_Framework_TestCase;

class IntegerCheckTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not an integer
     */
    public function testCheckThrowsExceptionIfNotAnInteger()
    {
        $mockParam = [
            'value' => 'some string',
        ];

        $integerCheck = new IntegerCheck;
        $integerCheck->check($mockParam);
    }

    public function testCheckIgnoresFormatIfNotDefined()
    {
        $mockParam = [
            'value' => 2147483647 + 1,
        ];

        $integerCheck = new IntegerCheck;
        $integerCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds int32 bounds
     */
    public function testCheckThrowsExceptionIfExceedsInt32Bounds()
    {
        $mockParam = [
            'value' => 2147483647 + 1,
            'format' => 'int32',
        ];

        $integerCheck = new IntegerCheck;
        $integerCheck->check($mockParam);
    }

    public function testCheckPassesIfWithinInt32Bounds()
    {
        $mockParam = [
            'value' => -2147483647 + 1,
            'format' => 'int32',
        ];

        $integerCheck = new IntegerCheck;
        $integerCheck->check($mockParam);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value exceeds int64 bounds
     */
    public function testCheckThrowsExceptionIfExceedsInt64Bounds()
    {
       $mockParam = [
           'value' => -9223372036854775807 - 1,
           'format' => 'int64',
       ];

       $integerCheck = new IntegerCheck;
       $integerCheck->check($mockParam);
    }

    public function testCheckPassesIfWithinInt64Bounds()
    {
        $mockParam = [
            'value' => 9223372036854775807 - 1,
        ];

        $integerCheck = new IntegerCheck;
        $integerCheck->check($mockParam);
    }
}
