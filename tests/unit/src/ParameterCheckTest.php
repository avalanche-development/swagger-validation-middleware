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
                'checkBodySchema',
                'checkParamValue',
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodySchema');
        $parameterCheck->expects($this->never())
            ->method('checkParamValue');
        $parameterCheck->expects($this->once())
            ->method('checkRequired')
            ->with($mockParam)
            ->willReturn(false);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamChecksBodySchemaIfBodyParam()
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
                'checkBodySchema',
                'checkParamValue',
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkBodySchema')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkParamValue');
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamChecksValueIfNotBodyParam()
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
                'checkBodySchema',
                'checkParamValue',
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodySchema');
        $parameterCheck->expects($this->once())
            ->method('checkParamValue')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->method('checkRequired')
            ->willReturn(true);

        $result = $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
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

    public function testCheckParamValueChecksItemsIfArray()
    {
        $mockParam = [
            'items' => [],
            'type' => 'array',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParamValue = $reflectedParameterCheck->getMethod('checkParamValue');
        $reflectedCheckParamValue->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkFormat',
                'checkItems',
                'checkLength',
                'checkPattern',
                'checkRange',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkFormat');
        $parameterCheck->expects($this->once())
            ->method('checkItems')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }
}
