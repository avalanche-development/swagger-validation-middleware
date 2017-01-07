<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

class SecurityCheckTest extends PHPUnit_Framework_TestCase
{

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $securityCheck = new SecurityCheck($mockRequest);

        $this->assertAttributeSame($mockRequest, 'request', $securityCheck);
    }

    public function testCheckSchemeUsesBasicCheckIfBasicType()
    {
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
            ->willReturn(true);
        $securityCheck->expects($this->never())
            ->method('checkOAuthScheme');

        $result = $securityCheck->checkScheme($mockScheme);

        $this->assertTrue($result);
    }

    public function testCheckSchemeUsesOAuthCheckIfOAuthType()
    {
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
            ->with($mockScheme)
            ->willReturn(true);

        $result = $securityCheck->checkScheme($mockScheme);

        $this->assertTrue($result);
    }

    public function testCheckSchemeReturnsFalseIfUnknownType()
    {
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

        $result = $securityCheck->checkScheme($mockScheme);

        $this->assertFalse($result);
    }

    public function testCheckBasicSchemePullsAuthorizationFromHeader() {}
    public function testCheckBasicSchemeReturnsFalseIfNotBasicAuth() {}
    public function testCheckBasicSchemeReturnsFalseIfInvalidAuth() {}
    public function testCheckBasicSchemeReturnsTrueIfValidAuthHeader() {}
}
