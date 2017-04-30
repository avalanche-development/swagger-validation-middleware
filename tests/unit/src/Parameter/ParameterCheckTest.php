<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter;

use Exception;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ParameterCheckTest extends PHPUnit_Framework_TestCase
{

    public function testConstructInstantiatesBooleanCheck()
    {
        $parameterCheck = new ParameterCheck;

        $this->assertAttributeInstanceOf(Format\BooleanCheck::class, 'booleanCheck', $parameterCheck);
    }

    public function testConstructInstantiatesIntegerCheck()
    {
        $parameterCheck = new ParameterCheck;

        $this->assertAttributeInstanceOf(Format\IntegerCheck::class, 'integerCheck', $parameterCheck);
    }

    public function testConstructInstantiatesNumberCheck()
    {
        $parameterCheck = new ParameterCheck;

        $this->assertAttributeInstanceOf(Format\NumberCheck::class, 'numberCheck', $parameterCheck);
    }

    public function testConstructInstantiatesStringCheck()
    {
        $parameterCheck = new ParameterCheck;

        $this->assertAttributeInstanceOf(Format\StringCheck::class, 'stringCheck', $parameterCheck);
    }

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

    public function testCheckRequiredContinuesOnRequiredSetParam()
    {
        $mockParam = [
            'required' => true,
            'value' => 'some string',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequired = $reflectedParameterCheck->getMethod('checkRequired');
        $reflectedCheckRequired->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckRequired->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckBodySchemaPassesAlongRefactoredParams()
    {
        $mockParam = [
            'schema' => [
                'type' => 'some type',
            ],
            'value' => 'some value',
        ];

        $refactoredParam = [
            'type' => 'some type',
            'value' => 'some value',
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckBodySchema = $reflectedParameterCheck->getMethod('checkBodySchema');
        $reflectedCheckBodySchema->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkParamValue',
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkParamValue')
            ->with($refactoredParam);

        $reflectedCheckBodySchema->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueChecksItemsIfArray()
    {
        $mockParam = [
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
            'value' => [
                'some value',
            ],
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
            ->method('checkItems')
            ->with($mockParam);

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueCallsCheckParamValueForEachItemInArray()
    {
        $mockParam = [
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
            'value' => [
                'value one',
                'value two',
            ],
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
        $parameterCheck->expects($this->exactly(count($mockParam['value'])))
            ->method('checkFormat')
            ->withConsecutive(
                [
                    $this->equalTo([
                        'type' => 'string',
                        'value' => 'value one',
                    ]),
                ],
                [
                    $this->equalTo([
                        'type' => 'string',
                        'value' => 'value two',
                    ]),
                ]
            );

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueChecksRequiredPropertiesIfObject()
    {
        $mockParam = [
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                ],
            ],
            'value' => [
                'key' => 'some value',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParamValue = $reflectedParameterCheck->getMethod('checkParamValue');
        $reflectedCheckParamValue->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkFormat',
                'checkRequiredProperties',
            ])
            ->getMock();
        $parameterCheck->expects($this->once())
            ->method('checkRequiredProperties')
            ->with($mockParam);

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueCallsCheckParamValueForEachItemInObject()
    {
        $mockParam = [
            'type' => 'object',
            'properties' => [
                'key_one' => [
                    'type' => 'string',
                ],
                'key_two' => [
                    'type' => 'string',
                ],
            ],
            'value' => [
                'key_one' => 'value one',
                'key_two' => 'value two',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParamValue = $reflectedParameterCheck->getMethod('checkParamValue');
        $reflectedCheckParamValue->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkFormat',
                'checkRequiredProperties',
            ])
            ->getMock();
        $parameterCheck->expects($this->exactly(count($mockParam['value'])))
            ->method('checkFormat')
            ->withConsecutive(
                [
                    $this->equalTo([
                        'type' => 'string',
                        'value' => 'value one',
                    ]),
                ],
                [
                    $this->equalTo([
                        'type' => 'string',
                        'value' => 'value two',
                    ]),
                ]
            );

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueSkipsEmptyProperties()
    {
        $mockParam = [
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                ],
            ],
            'value' => [],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckParamValue = $reflectedParameterCheck->getMethod('checkParamValue');
        $reflectedCheckParamValue->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkFormat',
                'checkRequiredProperties',
            ])
            ->getMock();
        $parameterCheck->expects($this->never())
            ->method('checkFormat');

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckParamValueChecksFormatIfNotArrayOrObject()
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

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException Exception
     */
    public function testCheckParamValueBailsIfCheckFormatFails()
    {
        $mockException = $this->createMock(Exception::class);

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
        $parameterCheck->method('checkFormat')
            ->will($this->throwException($mockException));

        $reflectedCheckParamValue->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckItemsPassesIfMaxItemsIsRespected()
    {
        $mockParam = [
            'maxItems' => 1,
            'value' => [
                'value one',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Size of array exceeds maxItems
     */
    public function testCheckItemsBailsIfMaxItemsIsViolated()
    {
        $mockParam = [
            'maxItems' => 1,
            'value' => [
                'value one',
                'value two',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckItemsPassesIfMinItemsIsRespected()
    {
        $mockParam = [
            'minItems' => 1,
            'value' => [
                'value one',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Size of array exceeds minItems
     */
    public function testCheckItemsBailsIfMinItemsIsViolated()
    {
        $mockParam = [
            'minItems' => 1,
            'value' => [],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckItemsPassesIfUniqueItemsIsRespected()
    {
        $mockParam = [
            'uniqueItems' => true,
            'value' => [
                'value one',
                'value two',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Duplicate array items found when should be unique
     */
    public function testCheckItemsBailsIfUniqueItemsIsViolated()
    {
        $mockParam = [
            'uniqueItems' => true,
            'value' => [
                'some value',
                'some value',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckItems = $reflectedParameterCheck->getMethod('checkItems');
        $reflectedCheckItems->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckItems->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckRequiredPropertiesPassesIfNoPropertiesSet()
    {
        $mockParam = [
            'properties' => [
                'key' => [
                    'type' => 'string',
                ],
            ],
            'value' => [],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequiredProperties = $reflectedParameterCheck->getMethod('checkRequiredProperties');
        $reflectedCheckRequiredProperties->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckRequiredProperties->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerValidationMiddleware\Parameter\ValidationException
     * @expectedExceptionMessage Required value was not set
     */
    public function testCheckRequiredPropertiesBailsIfRequiredPropertyNotSet()
    {
        $mockParam = [
            'required' => [
                'key',
            ],
            'properties' => [
                'key' => [
                    'type' => 'string',
                ],
            ],
            'value' => [],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequiredProperties = $reflectedParameterCheck->getMethod('checkRequiredProperties');
        $reflectedCheckRequiredProperties->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckRequiredProperties->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckRequiredPropertiesPassesIfRequiredPropertiesAreMet()
    {
        $mockParam = [
            'required' => [
                'key',
            ],
            'properties' => [
                'key' => [
                    'type' => 'string',
                ],
            ],
            'value' => [
                'key' => 'some value',
            ],
        ];

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedCheckRequiredProperties = $reflectedParameterCheck->getMethod('checkRequiredProperties');
        $reflectedCheckRequiredProperties->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedCheckRequiredProperties->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatBailsIfValueIsEmpty()
    {
        $mockParam = [
            'value' => '',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->never())
            ->method('check');
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->never())
            ->method('check');
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->never())
            ->method('check');
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->never())
            ->method('check');

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatChecksBooleanIfBoolean()
    {
        $mockParam = [
            'type' => 'boolean',
            'value' => 'some value',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->once())
            ->method('check')
            ->with($mockParam);
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->never())
            ->method('check');
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->never())
            ->method('check');
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->never())
            ->method('check');

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatCheckIntegerIfInteger()
    {
        $mockParam = [
            'type' => 'integer',
            'value' => 'some value',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->never())
            ->method('check');
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->once())
            ->method('check')
            ->with($mockParam);
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->never())
            ->method('check');
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->never())
            ->method('check');

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatChecksNumberIfNumber()
    {
        $mockParam = [
            'type' => 'number',
            'value' => 'some value',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->never())
            ->method('check');
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->never())
            ->method('check');
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->once())
            ->method('check')
            ->with($mockParam);
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->never())
            ->method('check');

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatChecksStringIfString()
    {
        $mockParam = [
            'type' => 'string',
            'value' => 'some value',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->never())
            ->method('check');
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->never())
            ->method('check');
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->never())
            ->method('check');
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->once())
            ->method('check')
            ->with($mockParam);

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }

    public function testCheckFormatPassesIfUnrecognizedType()
    {
        $mockParam = [
            'type' => 'some type',
            'value' => 'some value',
        ];

        $mockBooleanCheck = $this->getMockBuilder(Format\BooleanCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockBooleanCheck->expects($this->never())
            ->method('check');
        $mockIntegerCheck = $this->getMockBuilder(Format\IntegerCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockIntegerCheck->expects($this->never())
            ->method('check');
        $mockNumberCheck = $this->getMockBuilder(Format\NumberCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockNumberCheck->expects($this->never())
            ->method('check');
        $mockStringCheck = $this->getMockBuilder(Format\StringCheck::class)
            ->setMethods([ 'check' ])
            ->getMock();
        $mockStringCheck->expects($this->never())
            ->method('check');

        $reflectedParameterCheck = new ReflectionClass(ParameterCheck::class);
        $reflectedBooleanCheckValue = $reflectedParameterCheck->getProperty('booleanCheck');
        $reflectedBooleanCheckValue->setAccessible(true);
        $reflectedIntegerCheckValue = $reflectedParameterCheck->getProperty('integerCheck');
        $reflectedIntegerCheckValue->setAccessible(true);
        $reflectedNumberCheckValue = $reflectedParameterCheck->getProperty('numberCheck');
        $reflectedNumberCheckValue->setAccessible(true);
        $reflectedStringCheckValue = $reflectedParameterCheck->getProperty('stringCheck');
        $reflectedStringCheckValue->setAccessible(true);
        $reflectedCheckFormat = $reflectedParameterCheck->getMethod('checkFormat');
        $reflectedCheckFormat->setAccessible(true);

        $parameterCheck = $this->getMockBuilder(ParameterCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedBooleanCheckValue->setValue($parameterCheck, $mockBooleanCheck);
        $reflectedIntegerCheckValue->setValue($parameterCheck, $mockIntegerCheck);
        $reflectedNumberCheckValue->setValue($parameterCheck, $mockNumberCheck);
        $reflectedStringCheckValue->setValue($parameterCheck, $mockStringCheck);
        $reflectedCheckFormat->invokeArgs($parameterCheck, [ $mockParam ]);
    }
}
