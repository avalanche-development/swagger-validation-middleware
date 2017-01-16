<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

class SecurityCheckTest extends PHPUnit_Framework_TestCase
{

    public function testCheckSecurityPassesEachSchemeAgainstSecurityCheck()
    {
        $mockSecurity = [
            [ 'one' ],
            [ 'two' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkScheme' ])
            ->getMock();
        $securityCheck->expects($this->exactly(count($mockSecurity)))
            ->method('checkScheme')
            ->withConsecutive(
                [
                    $mockRequest,
                    $mockSecurity[0],
                ],
                [
                    $mockRequest,
                    $mockSecurity[1],
                ]
            );

        $securityCheck->checkSecurity($mockRequest, $mockSecurity);
    }

    public function testCheckSecurityReturnsTrueIfSecurityCheckReturnsTrueForAtLeastOneScheme()
    {
        $mockSecurity = [
            [ 'valid' ],
            [ 'invalid' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkScheme' ])
            ->getMock();
        $securityCheck->method('checkScheme')
            ->will($this->returnCallback(function ($mockRequest, $scheme) {
                return current($scheme) === 'valid';
            }));

        $result = $securityCheck->checkSecurity($mockRequest, $mockSecurity);

        $this->assertTrue($result);
    }

    public function testCheckSecurityReturnsFalseIfSecurityCheckReturnsFalseForAllSchemes()
    {
        $mockSecurity = [
            [ 'invalid' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $securityCheck = $this->getMockBuilder(SecurityCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkScheme' ])
            ->getMock();
        $securityCheck->method('checkScheme')
            ->willReturn(false);

        $result = $securityCheck->checkSecurity($mockRequest, $mockSecurity);

        $this->assertFalse($result);
    }

    public function testCheckSchemeUsesBasicCheckIfBasicType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'basic',
        ];

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckScheme = $reflectedSecurityCheck->getMethod('checkScheme');
        $reflectedCheckScheme->setAccessible(true);

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

        $result = $reflectedCheckScheme->invokeArgs($securityCheck, [
            $mockRequest,
            $mockScheme,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckSchemeUsesOAuthCheckIfOAuthType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'oauth',
        ];

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckScheme = $reflectedSecurityCheck->getMethod('checkScheme');
        $reflectedCheckScheme->setAccessible(true);

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

        $result = $reflectedCheckScheme->invokeArgs($securityCheck, [
            $mockRequest,
            $mockScheme,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckSchemeReturnsFalseIfUnknownType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockScheme = [
            'type' => 'invalid',
        ];

        $reflectedSecurityCheck = new ReflectionClass(SecurityCheck::class);
        $reflectedCheckScheme = $reflectedSecurityCheck->getMethod('checkScheme');
        $reflectedCheckScheme->setAccessible(true);

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

        $result = $reflectedCheckScheme->invokeArgs($securityCheck, [
            $mockRequest,
            $mockScheme,
        ]);

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
