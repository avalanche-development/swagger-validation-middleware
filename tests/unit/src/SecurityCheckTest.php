<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

class SecurityCheckTest extends PHPUnit_Framework_TestCase
{

    public function testCheckSchemeUsesBasicCheckIfBasicType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'basic',
        ];

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBasicScheme',
                'checkOAuthScheme',
            ])
            ->getMock();
        $securityCheck->expects($this->once())
            ->method('checkBasicScheme')
            ->with($mockRequest)
            ->willReturn(true);
        $securityCheck->expects($this->never())
            ->method('checkOAuthScheme');

        $result = $securityCheck->checkScheme($mockRequest, $mockScheme);

        $this->assertTrue($result);
    }

    public function testCheckSchemeUsesOAuthCheckIfOAuthType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'oauth',
        ];

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBasicScheme',
                'checkOAuthScheme',
            ])
            ->getMock();
        $securityCheck->expects($this->never())
            ->method('checkBasicScheme');
        $securityCheck->expects($this->once())
            ->method('checkOAuthScheme')
            ->with($mockRequest, $mockScheme)
            ->willReturn(true);

        $result = $securityCheck->checkScheme($mockRequest, $mockScheme);

        $this->assertTrue($result);
    }

    public function testCheckSchemeReturnsFalseIfUnknownType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'invalid',
        ];

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBasicScheme',
                'checkOAuthScheme',
            ])
            ->getMock();
        $securityCheck->expects($this->never())
            ->method('checkBasicScheme');
        $securityCheck->expects($this->never())
            ->method('checkOAuthScheme');

        $result = $securityCheck->checkScheme($mockRequest, $mockScheme);

        $this->assertFalse($result);
    }

    public function testCheckBasicSchemePullsAuthorizationFromHeader()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn('Basic Header');

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckBasicScheme = $reflectedSecurityCheck->getMethod('checkBasicScheme');
        $reflectedCheckBasicScheme->setAccessible(true);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckBasicScheme->invokeArgs($securityCheck, [ $mockRequest ]);
    }

    public function testCheckBasicSchemeReturnsFalseIfNotBasicAuth()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeader')
            ->willReturn('Bearer Header');

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckBasicScheme = $reflectedSecurityCheck->getMethod('checkBasicScheme');
        $reflectedCheckBasicScheme->setAccessible(true);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckBasicScheme->invokeArgs($securityCheck, [ $mockRequest ]);

        $this->assertFalse($result);
    }

    public function testCheckBasicSchemeReturnsFalseIfInvalidAuth()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeader')
            ->willReturn('Basic ');

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckBasicScheme = $reflectedSecurityCheck->getMethod('checkBasicScheme');
        $reflectedCheckBasicScheme->setAccessible(true);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckBasicScheme->invokeArgs($securityCheck, [ $mockRequest ]);

        $this->assertFalse($result);
    }

    public function testCheckBasicSchemeReturnsTrueIfValidAuthHeader()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeader')
            ->willReturn('Basic Header');

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckBasicScheme = $reflectedSecurityCheck->getMethod('checkBasicScheme');
        $reflectedCheckBasicScheme->setAccessible(true);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckBasicScheme->invokeArgs($securityCheck, [ $mockRequest ]);

        $this->assertTrue($result);
    }
}
