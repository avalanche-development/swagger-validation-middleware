<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

class ParameterCheckTest extends PHPUnit_Framework_TestCase
{

    public function testCheckParamsPassesEachParamAgainstParamCheck()
    {
        $mockParams = [
            [ 'one' ],
            [ 'two' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->expects($this->exactly(count($mockParams)))
            ->method('checkParam')
            ->withConsecutive(
                [
                    $mockRequest,
                    $mockParams[0],
                ],
                [
                    $mockRequest,
                    $mockParams[1],
                ]
            )
            ->willReturn(true);

        $parameterCheck->checkParams($mockRequest, $mockParams);
    }

    public function testCheckParamsReturnsTrueIfAllParamsAreValid()
    {
        $mockParams = [
            [ 'valid' ],
            [ 'valid' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->method('checkParam')
            ->willReturn(true);

        $result = $parameterCheck->checkParams($mockRequest, $mockParams);

        $this->assertTrue($result);
    }

    public function testCheckParamsReturnsFalseIfOneParamReturnsFalse()
    {
        $mockParams = [
            [ 'valid' ],
            [ 'invalid' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->method('checkParam')
            ->will($this->returnCallback(function ($mockRequest, $param) {
                return current($param) === 'valid';
            }));

        $result = $parameterCheck->checkParams($mockRequest, $mockParams);

        $this->assertFalse($result);
    }
}
