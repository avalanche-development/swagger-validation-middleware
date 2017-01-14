<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

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

    public function testCheckBasicSchemePullsAuthorizationFromHeader() {}
    public function testCheckBasicSchemeReturnsFalseIfNotBasicAuth() {}
    public function testCheckBasicSchemeReturnsFalseIfInvalidAuth() {}
    public function testCheckBasicSchemeReturnsTrueIfValidAuthHeader() {}
}
