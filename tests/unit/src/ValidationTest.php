<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ValidationTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $validation = new Validation;

        $this->assertInstanceOf(LoggerAwareInterface::class, $validation);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;
        $validation = new Validation;

        $this->assertAttributeEquals($logger, 'logger', $validation);
    }

    public function testInvokePassesAlongResponseFromCallStack()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockCallStackResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) use ($mockCallStackResponse) {
            return $mockCallStackResponse;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockCallStackResponse, $result);
    }
}
