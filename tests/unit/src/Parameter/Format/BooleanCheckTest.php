<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\Format;

use PHPUnit_Framework_TestCase;

class BooleanCheckTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Value is not a boolean
     */
    public function testCheckThrowsExceptionIfNotABoolean()
    {
        $mockParam = [
            'value' => 'some string',
        ];

        $booleanCheck = new BooleanCheck;
        $booleanCheck->check($mockParam);
    }

    public function testCheckContinuesNormallyIfOkay()
    {
        $mockParam = [
            'value' => 'false',
        ];

        $booleanCheck = new BooleanCheck;
        $booleanCheck->check($mockParam);
    }
}
