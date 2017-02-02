<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ParameterCheckTest extends PHPUnit_Framework_TestCase
{

    public function testCheckParamsPassesEachParamAgainstParamCheck()
    {
        $mockParams = [
            [ 'one' ],
            [ 'two' ],
        ];

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->expects($this->exactly(count($mockParams)))
            ->method('checkParam')
            ->withConsecutive(
                [ $mockParams[0] ],
                [ $mockParams[1] ]
            )
            ->willReturn(true);

        $parameterCheck->checkParams($mockParams);
    }

    public function testCheckParamsDoesNotThrowExceptionIfAllParamsAreValid()
    {
        $mockParams = [
            [ 'valid' ],
            [ 'valid' ],
        ];

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->method('checkParam')
            ->willReturn(true);

        $parameterCheck->checkParams($mockParams);
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

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'checkParam' ])
            ->getMock();
        $parameterCheck->method('checkParam')
            ->will($this->returnCallback(function ($param) {
                return current($param) === 'valid';
            }));

        $parameterCheck->checkParams($mockParams);
    }

    public function testCheckParamBailsIfChecksRequiredFails()
    {
        $mockParam = [
            'some requirement',
        ];

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
                'checkRequired',
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
        $parameterCheck->expects($this->once())
            ->method('checkRequired')
            ->with($mockParam)
            ->willReturn(false);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamChecksBodyIfBodyParam()
    {
        $mockParam = [
            'in' => 'body',
        ];

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
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkBodyParam')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksFormIfFormParam()
    {
        $mockParam = [
            'in' => 'formData',
        ];

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
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->once())
            ->method('checkFormParam')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkHeaderParam');
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksHeaderIfHeaderParam()
    {
        $mockParam = [
            'in' => 'header',
        ];

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
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodyParam');
        $parameterCheck->expects($this->never())
            ->method('checkFormParam');
        $parameterCheck->expects($this->once())
            ->method('checkHeaderParam')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkPathParam');
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksPathIfPathParam()
    {
        $mockParam = [
            'in' => 'path',
        ];

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
                'checkRequired',
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
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkQueryParam');
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksQueryIfQueryParam()
    {
        $mockParam = [
            'in' => 'query',
        ];

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
                'checkRequired',
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
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

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
                'checkRequired',
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
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckRequiredIgnoresUnsetRequiredSetting()
    {
        $mockParam = [];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequired = $reflectedParameterCheck->getMethod('checkRequired');
        $reflectedCheckRequired->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckRequiredIgnoresNonrequiredParam()
    {
        $mockParam = [
            'required' => false,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequired = $reflectedParameterCheck->getMethod('checkRequired');
        $reflectedCheckRequired->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider checkRequiredParamsProvider
     */
    public function testCheckRequiredReturnsBasedOnParamSet($param, $expected)
    {
        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequired = $reflectedParameterCheck->getMethod('checkRequired');
        $reflectedCheckRequired->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRequired->invokeArgs($parameterCheck, [ $param ]);
        
        $this->assertSame(
            $expected,
            $result,
            'Expected ' . json_encode($param) . ' to be ' . ($expected ? 'true' : 'false')
        );
    }

    public function checkRequiredParamsProvider()
    {
        return [
            [ [ 'required' => true ], false ],
            [ [ 'required' => true, 'value' => '' ], true ],
            [ [ 'required' => false, 'value' => null ], true ],
            [ [ 'required' => true, 'value' => null ], false ],
            [ [ 'required' => true, 'value' => 'puppies' ], true ],
            [ [ 'required' => true, 'value' => 0 ], true ],
        ];
    }
}
