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

    public function testCheckParamBailsIfCheckRequiredFails()
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

    public function testCheckParamValueBailsIfArrayCheckItemsFails()
    {
        $mockParam = [
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
        $parameterCheck->method('checkItems')
            ->willReturn(false);
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamValueChecksFormatIfNotArray()
    {
        $mockParam = [
            'type' => 'boolean',
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
        $parameterCheck->expects($this->once())
            ->method('checkFormat')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamValueBailsIfCheckFormatFails()
    {
        $mockParam = [
            'type' => 'string',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(false);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamValueChecksRangeIfNumber()
    {
        $mockParam = [
            'type' => 'number',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->once())
            ->method('checkRange')
            ->with($mockParam)
            ->willReturn(true);

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamValueBailsIfCheckRangeFails()
    {
        $mockParam = [
            'type' => 'number',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->expects($this->never())
            ->method('checkLength');
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->method('checkRange')
            ->willReturn(false);

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamValueChecksLengthIfString()
    {
        $mockParam = [
            'type' => 'string',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->expects($this->once())
            ->method('checkLength')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->method('checkPattern')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamValueBailsIfCheckLengthFails()
    {
        $mockParam = [
            'type' => 'string',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->method('checkLength')
            ->willReturn(false);
        $parameterCheck->expects($this->never())
            ->method('checkPattern');
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckParamValueChecksPatternIfString()
    {
        $mockParam = [
            'type' => 'string',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->method('checkLength')
            ->willReturn(true);
        $parameterCheck->expects($this->once())
            ->method('checkPattern')
            ->with($mockParam)
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckParamValueBailsIfCheckPatternFails()
    {
        $mockParam = [
            'type' => 'string',
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
        $parameterCheck->method('checkFormat')
            ->willReturn(true);
        $parameterCheck->expects($this->never())
            ->method('checkItems');
        $parameterCheck->method('checkLength')
            ->willReturn(true);
        $parameterCheck->method('checkPattern')
            ->willReturn(false);
        $parameterCheck->expects($this->never())
            ->method('checkRange');

        $result = $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckLengthBailsIfValueIsEmpty()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckLength = $reflectedParameterCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckLength->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckLengthFailsIfValueIsTooLong()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0123456789',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckLength = $reflectedParameterCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckLength->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckLengthFailsIfValueIsTooShort()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckLength = $reflectedParameterCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckLength->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckLengthPassesIfValueIsJustRight()
    {
        $mockParam = [
            'maxLength' => 5,
            'minLength' => 2,
            'value' => '0123',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckLength = $reflectedParameterCheck->getMethod('checkLength');
        $reflectedCheckLength->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckLength->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckPatternBailsIfValueIsEmpty()
    {
        $mockParam = [
            'pattern' => '^[a-z]$',
            'value' => '',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckPattern = $reflectedParameterCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckPattern->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckPatternBailsIfPatternIsEmpty()
    {
        $mockParam = [
            'value' => 'happy',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckPattern = $reflectedParameterCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckPattern->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider checkPatternProvider
     */
    public function testCheckPatternHandlesDifferentValues($param, $expected)
    {
        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckPattern = $reflectedParameterCheck->getMethod('checkPattern');
        $reflectedCheckPattern->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckPattern->invokeArgs($parameterCheck, [ $param ]);

        $this->assertSame(
            $expected,
            $result,
            'Expected ' . json_encode($param) . ' to be ' . ($expected ? 'true' : 'false')
        );
    }

    public function checkPatternProvider()
    {
        return [
            [ [ 'pattern' => '^[a-z]$', 'value' => '1234' ], false ],
            [ [ 'pattern' => '\d+', 'value' => 'abc' ], false ],
            [ [ 'pattern' => '^[0-1]{4}$', 'value' => '1010' ], true ],
            [ [ 'pattern' => '\d0\d', 'value' => '101' ], true ],
            [ [ 'pattern' => '^[night|day]$', 'value' => 'morning' ], false ],
            [ [ 'pattern' => '\w+', 'value' => '  ' ], false ],
        ];
    }


}
