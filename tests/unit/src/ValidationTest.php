<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use AvalancheDevelopment\SwaggerRouterMiddleware\ParsedSwaggerInterface;
use Exception;
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

    public function testConstructSetsHeaderCheck()
    {
        $headerCheck = new HeaderCheck;
        $validation = new Validation;

        $this->assertAttributeEquals($headerCheck, 'headerCheck', $validation);
    }

    public function testConstructSetsParameterCheck()
    {
        $parameterCheck = new ParameterCheck;
        $validation = new Validation;

        $this->assertAttributeEquals($parameterCheck, 'parameterCheck', $validation);
    }

    public function testConstructSetsSecurityCheck()
    {
        $securityCheck = new SecurityCheck;
        $validation = new Validation;

        $this->assertAttributeEquals($securityCheck, 'securityCheck', $validation);
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

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->expects($this->never())
            ->method('checkSecurity');

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'log',
            ])
            ->getMock();
        $validation->expects($this->once())
            ->method('log')
            ->with('no swagger information found in request, skipping');

        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeChecksRequestSecurityAgainstAllowedSecurities()
    {
        $allowedSecurities = [
            'type' => 'basic',
        ];

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getSecurity')
            ->willReturn($allowedSecurities);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->expects($this->once())
            ->method('checkSecurity')
            ->with($mockRequest, $allowedSecurities)
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfUnacceptableSecurityInRequest()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->never())
            ->method('getConsumes');
        $mockSwagger->expects($this->never())
            ->method('getParams');
        $mockSwagger->expects($this->never())
            ->method('getProduces');
        $mockSwagger->expects($this->never())
            ->method('getSchemes');
        $mockSwagger->expects($this->once())
            ->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->will($this->throwException($mockException));

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->expects($this->never())
            ->method('checkScheme');
        $validation->expects($this->never())
            ->method('log');

        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokeChecksRequestSchemeAgainstAllowedSchemes()
    {
        $allowedSchemes = [
            'http',
            'https',
        ];

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getSchemes')
            ->willReturn($allowedSchemes);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfUnacceptableSchemeInRequest()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->never())
            ->method('getConsumes');
        $mockSwagger->expects($this->never())
            ->method('getParams');
        $mockSwagger->expects($this->never())
            ->method('getProduces');
        $mockSwagger->expects($this->once())
            ->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->expects($this->never())
            ->method('checkIncomingContent');

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

        $validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkScheme',
                'log',
            ])
            ->getMock();
        $validation->method('checkScheme')
            ->will($this->throwException($mockException));
        $validation->expects($this->never())
            ->method('log');

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokeChecksRequestContentAgainstAllowedConsumes()
    {
        $allowedConsumeTypes = [
            'application/json',
        ];

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getConsumes')
            ->willReturn($allowedConsumeTypes);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->expects($this->once())
            ->method('checkIncomingContent')
            ->with($mockRequest, $allowedConsumeTypes)
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfUnacceptableContentInRequest()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->expects($this->never())
            ->method('getParams');
        $mockSwagger->expects($this->never())
            ->method('getProduces');
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->will($this->throwException($mockException));

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->expects($this->never())
            ->method('checkParams');

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokeChecksRequestParameters()
    {
        $params = [
            'some params'
        ];

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->expects($this->once())
            ->method('checkParams')
            ->with($params)
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfParameterCheckFails()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([]);
        $mockSwagger->expects($this->never())
            ->method('getProduces');
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            throw new \Exception('callable should not be called');
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->will($this->throwException($mockException));

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokePassesAlongResponseFromCallStack()
    {
        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockCallStackResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) use ($mockCallStackResponse) {
            return $mockCallStackResponse;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockCallStackResponse, $result);
    }

    public function testInvokeChecksResponseContentAgainstAllowedProduces()
    {
        $allowedProduceTypes = [
            'application/json',
        ];

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getProduces')
            ->willReturn($allowedProduceTypes);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->expects($this->once())
            ->method('checkOutgoingContent')
            ->with($mockResponse, $allowedProduceTypes)
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfUnproducibleContentInResponse()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->expects($this->once())
            ->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->will($this->throwException($mockException));
        $mockHeaderCheck->expects($this->never())
            ->method('checkAcceptHeader');

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testInvokeChecksResponseContentAgainstRequestAccept()
    {
        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->expects($this->once())
            ->method('checkAcceptHeader')
            ->with($mockRequest, $mockResponse)
            ->willReturn(true);

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $result = $validation->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeBailsIfUnacceptableContentInResponse()
    {
        $mockException = $this->createMock(Exception::class);

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getConsumes')
            ->willReturn([]);
        $mockSwagger->method('getParams')
            ->willReturn([]);
        $mockSwagger->method('getProduces')
            ->willReturn([]);
        $mockSwagger->method('getSchemes')
            ->willReturn([]);
        $mockSwagger->method('getSecurity')
            ->willReturn([]);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $mockHeaderCheck = $this->createMock(HeaderCheck::class);
        $mockHeaderCheck->method('checkIncomingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkOutgoingContent')
            ->willReturn(true);
        $mockHeaderCheck->method('checkAcceptHeader')
            ->will($this->throwException($mockException));

        $mockParameterCheck = $this->createMock(ParameterCheck::class);
        $mockParameterCheck->method('checkParams')
            ->willReturn(true);

        $mockSecurityCheck = $this->createMock(SecurityCheck::class);
        $mockSecurityCheck->method('checkSecurity')
            ->willReturn(true);

        $reflectedValidation = new ReflectionClass(Validation::class);
        $reflectedHeaderCheck = $reflectedValidation->getProperty('headerCheck');
        $reflectedHeaderCheck->setAccessible(true);
        $reflectedParameterCheck = $reflectedValidation->getProperty('parameterCheck');
        $reflectedParameterCheck->setAccessible(true);
        $reflectedSecurityCheck = $reflectedValidation->getProperty('securityCheck');
        $reflectedSecurityCheck->setAccessible(true);

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

        $reflectedHeaderCheck->setValue($validation, $mockHeaderCheck);
        $reflectedParameterCheck->setValue($validation, $mockParameterCheck);
        $reflectedSecurityCheck->setValue($validation, $mockSecurityCheck);

        $validation->__invoke($mockRequest, $mockResponse, $mockCallable);
    }

    public function testCheckSchemeDoesNotThrowExceptionIfRequestSchemeIsAllowed()
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

        $reflectedCheckScheme->invokeArgs($validation, [
            $mockRequest,
            $mockSchemes,
        ]);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\NotFound
     * @expectedExceptionMessage Unallowed scheme (http) in request
     */
    public function testCheckSchemeThrowsExceptionIfRequestSchemeNotAllowed()
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

        $reflectedCheckScheme->invokeArgs($validation, [
            $mockRequest,
            $mockSchemes,
        ]);
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
