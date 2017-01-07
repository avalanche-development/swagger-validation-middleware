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

    public function testCheckSchemeUsesBasicCheckIfBasicType() {}
    public function testCheckSchemeUsesOAuthCheckIfOAuthType() {}
    public function testCheckSchemeReturnsFalseIfUnknownType() {}

    public function testCheckBasicSchemePullsAuthorizationFromHeader() {}
    public function testCheckBasicSchemeReturnsFalseIfNotBasicAuth() {}
    public function testCheckBasicSchemeReturnsFalseIfInvalidAuth() {}
    public function testCheckBasicSchemeReturnsTrueIfValidAuthHeader() {}
}
