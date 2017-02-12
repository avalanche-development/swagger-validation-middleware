<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter;

use Exception;
use PHPUnit_Framework_TestCase;

class ValidationExceptionTest extends PHPUnit_Framework_TestCase
{

    public function testExtendsException()
    {
        $exception = new ValidationException;

        $this->assertInstanceOf(Exception::class, $exception);
    }
}
