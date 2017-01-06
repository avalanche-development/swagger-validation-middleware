<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
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
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->expects($this->never())
            ->method('checkScheme');
        $validation->expects($this->never())
            ->method('checkSecurity');
        $validation->expects($this->once())
            ->method('log')
            ->with('no swagger information found in request, skipping');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeChecksRequestSecurityAgainstAllowedSecurities()
    {
        $allowedSecurities = [
            'type' => 'basic',
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn([
                'schemes' => [],
                'security' => $allowedSecurities,
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->willReturn(true);
        $validation->expects($this->once())
            ->method('checkSecurity')
            ->with(
                $this->isInstanceOf(SecurityValidation::class),
                $allowedSecurities
            )
            ->willReturn(true);
        $validation->expects($this->never())
            ->method('log');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\Unauthorized
     * @expectedExceptionMessage Unacceptable security passed in request
     */
    public function testInvokeBailsIfUnacceptableSecurityInRequest()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn([
                'schemes' => [],
                'security' => [],
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->expects($this->never())
            ->method('checkScheme');
        $validation->method('checkSecurity')
            ->willReturn(false);
        $validation->expects($this->never())
            ->method('log');

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
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
                'security' => [],
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->expects($this->once())
            ->method('checkScheme')
            ->with($mockRequest, $allowedSchemes)
            ->willReturn(true);
        $validation->method('checkSecurity')
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
                'security' => [],
            ]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->willReturn(false);
        $validation->method('checkSecurity')
            ->willReturn(true);
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
                'schemes' => [],
                'security' => [],
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
                'checkSecurity',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->willReturn(true);
        $validation->method('checkSecurity')
            ->willReturn(true);
        $validation->expects($this->never())
            ->method('log');

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockCallStackResponse, $result);
    }

    public function testCheckSecurityPassesEachSchemeAgainstSecurityValidation()
    {
        $mockSecurity = [
            [ 'one' ],
            [ 'two' ],
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $mockSecurityValidation = $this->getMockBuilder(SecurityValidation::class)
            ->setConstructorArgs([ $mockRequest ])
            ->getMock();
        $mockSecurityValidation->expects($this->exactly(count($mockSecurity)))
            ->method('checkScheme')
            ->withConsecutive(
              [ $mockSecurity[0] ],
              [ $mockSecurity[1] ]
            );

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedCheckSecurity = $reflectedValidation->getMethod('checkSecurity');
        $reflectedCheckSecurity->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedCheckSecurity->invokeArgs($validation, [
            $mockSecurityValidation,
            $mockSecurity,
        ]);
    }

    public function testCheckSecurityReturnsTrueIfSecurityValidationReturnsTrueForAtLeastOneScheme()
    {
        $mockSecurity = [
            [ 'valid' ],
            [ 'invalid' ],
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $mockSecurityValidation = $this->getMockBuilder(SecurityValidation::class)
            ->setConstructorArgs([ $mockRequest ])
            ->getMock();
        $mockSecurityValidation->method('checkScheme')
            ->will($this->returnCallback(function ($scheme) {
                return current($scheme) === 'valid';
            }));

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedCheckSecurity = $reflectedValidation->getMethod('checkSecurity');
        $reflectedCheckSecurity->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedCheckSecurity->invokeArgs($validation, [
            $mockSecurityValidation,
            $mockSecurity,
        ]);
        $this->assertTrue($result);
    }

    public function testCheckSecurityReturnsFalseIfSecurityValidationReturnsFalseForAllSchemes()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $mockSecurity = [
            [ 'invalid' ],
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $mockSecurityValidation = $this->getMockBuilder(SecurityValidation::class)
            ->setConstructorArgs([ $mockRequest ])
            ->getMock();
        $mockSecurityValidation->method('checkScheme')
            ->willReturn(false);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedCheckSecurity = $reflectedValidation->getMethod('checkSecurity');
        $reflectedCheckSecurity->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedCheckSecurity->invokeArgs($validation, [
            $mockSecurityValidation,
            $mockSecurity,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckSchemeReturnsTrueIfRequestSchemeIsAllowed()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->expects($this->once())
            ->method('getScheme')
            ->willReturn('http');
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getUri')
            ->willReturn($mockUri);

        $mockSchemes = [ 'http' ];

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedCheckScheme = $reflectedValidation->getMethod('checkScheme');
        $reflectedCheckScheme->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedCheckScheme->invokeArgs($validation, [
            $mockRequest,
            $mockSchemes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckSchemeReturnsFalseIfRequestSchemeNotAllowed()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->expects($this->once())
            ->method('getScheme')
            ->willReturn('http');
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getUri')
            ->willReturn($mockUri);

        $mockSchemes = [ 'https' ];

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedCheckScheme = $reflectedValidation->getMethod('checkScheme');
        $reflectedCheckScheme->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedCheckScheme->invokeArgs($validation, [
            $mockRequest,
            $mockSchemes,
        ]);

        $this->assertFalse($result);
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
