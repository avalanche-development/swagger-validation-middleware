<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

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

    public function testCheckParamsDoesNotThrowExceptionIfAllParamsAreValid()
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

        $parameterCheck->checkParams($mockRequest, $mockParams);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\BadRequest
     * @expectedExceptionMessage Bad parameters passed in request
     */
    public function testCheckParamsThrowsExceptionIfOneParamReturnsFalse()
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

        $parameterCheck->checkParams($mockRequest, $mockParams);
    }

    public function testCheckParamChecksBodyIfBodyParam()
    {
        $mockParam = [
            'in' => 'body',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkBodyParam')
            ->with($mockRequest, $mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksFormIfFormParam()
    {
        $mockParam = [
            'in' => 'formData',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->once())
            ->method('checkFormParam')
            ->with($mockRequest, $mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksHeaderIfHeaderParam()
    {
        $mockParam = [
            'in' => 'header',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->once())
            ->method('checkHeaderParam')
            ->with($mockRequest, $mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksPathIfPathParam()
    {
        $mockParam = [
            'in' => 'path',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->once())
            ->method('checkPathParam')
            ->with($mockRequest, $mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksQueryIfQueryParam()
    {
        $mockParam = [
            'in' => 'query',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->once())
            ->method('checkQueryParam')
            ->with($mockRequest, $mockParam)
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);

        $this->assertTrue($result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid location set for parameter
     */
    public function testCheckParamThrowsErrorIfUnrecognizedLocation()
    {
        $mockParam = [
            'in' => 'invalid',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParam = $reflectedParameterCheck->getMethod('checkParam');
        $reflectedCheckParam->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBodyParam',
                'checkFormParam',
                'checkHeaderParam',
                'checkPathParam',
                'checkQueryParam',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');

        $reflectedCheckParam->invokeArgs($parameterCheck, [
            $mockRequest,
            $mockParam,
        ]);
    }
}
