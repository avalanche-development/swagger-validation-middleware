<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter;

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
            );

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
                if (current($param) === 'invalid') {
                    throw new ValidationException('oh noes');
                }
            }));

        $parameterCheck->checkParams($mockParams);
    }

    public function testCheckParamChecksRequired()
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
                'checkBodySchema',
                'checkParamValue',
                'checkRequired',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkBodySchema');
        $parameterCheck->expects($this->once())
            ->method('checkRequired')
            ->with($mockParam);

        $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);
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
            ->with($mockParam);
        $parameterCheck->expects($this->never())
            ->method('checkParamValue');

        $reflectedCheckParam->invokeArgs($parameterCheck, [ $mockParam ]);
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
            ->with($mockParam);

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

        $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);
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

        $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Required value was not set
     */
    public function testCheckRequiredThrowsExceptionOnInvalidRequiredField()
    {
        $mockParam = [
            'required' => true,
            'value' => null,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequired = $reflectedParameterCheck->getMethod('checkRequired');
        $reflectedCheckRequired->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);
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
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkFormat');
        $parameterCheck->expects($this->once())
            ->method('checkItems')
            ->with($mockParam);

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueBailsIfArrayCheckItemsFails()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'type' => 'array',
            'items' => [],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParamValue = $reflectedParameterCheck->getMethod('checkParamValue');
        $reflectedCheckParamValue->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkFormat',
                'checkItems',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkFormat');

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
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
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkFormat')
            ->with($mockParam);
        $parameterCheck->expects($this->never())
            ->method('checkItems');

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueBailsIfCheckFormatFails()
    {
        $this->markTestIncomplete();

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
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkItems');

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckRangeBailsIfValueIsEmpty()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'maximum' => 5,
            'minimum' => 2,
            'value' => null,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
    }

    public function testCheckRangeFailsIfValueIsExceedsMaximum()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'maximum' => 5,
            'minimum' => 2,
            'value' => 6,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckRangeFailsIfValueIsExceedsExclusiveMaximum()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'exclusiveMaximum' => 5,
            'value' => 5,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckRangeFailsIfValueIsExceedsMinimum()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'minimum' => 5,
            'minimum' => 2,
            'value' => 1,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckRangeFailsIfValueIsExceedsExclusiveMinimum()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'exclusiveMinimum' => 2,
            'value' => 2,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertFalse($result);
    }

    public function testCheckRangePassesIfValueIsJustRight()
    {
        $this->markTestIncomplete();

        $mockParam = [
            'maximum' => 5,
            'minimum' => 2,
            'value' => 4,
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRange = $reflectedParameterCheck->getMethod('checkRange');
        $reflectedCheckRange->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckRange->invokeArgs($parameterCheck, [ $mockParam ]);

        $this->assertTrue($result);
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
