<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

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

    public function testInvokeBailsIfNoSwaggerFound()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn(null);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->expects($this->never())
            ->method('checkScheme');
        $validation->expects($this->once())
            ->method('log')
            ->with('no swagger information found in request, skipping');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeChecksRequestSchemeAgainstAllowedSchemes()
    {
        $allowedSchemes = [
            'http',
            'https',
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn([
                'schemes' => $allowedSchemes,
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->expects($this->once())
            ->method('checkScheme')
            ->with($mockRequest, $allowedSchemes)
            ->willReturn(true);
        $validation->expects($this->never())
            ->method('log');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\NotFound
     * @expectedExceptionMessage Unallowed scheme in request
     */
    public function testInvokeBailsIfUnacceptableSchemeInRequest()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn([
                'schemes' => [],
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->willReturn(false);
        $validation->expects($this->never())
            ->method('log');

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokePassesAlongResponseFromCallStack()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn([
                'schemes' => []
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockCallStackResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) use ($mockCallStackResponse) {
            return $mockCallStackResponse;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->willReturn(true);
        $validation->expects($this->never())
            ->method('log');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockCallStackResponse, $result);
    }

    public function testLog()
    {
        $message = 'test debug message';

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with("swagger-validation-middleware: {$message}");

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedLog = $reflectedValidation->getMethod('log');
        $reflectedLog->setAccessible(true);
        $reflectedLogger = $reflectedValidation->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedLogger->setValue($validation, $mockLogger);
        $reflectedLog->invokeArgs($validation, [
            $message,
        ]);       
    }
}
