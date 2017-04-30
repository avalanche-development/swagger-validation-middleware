<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware\Parameter;

use AvalancheDevelopment\Peel\HttpError;

class ParameterCheck
{

    /** @var Format\BooleanCheck */
    protected $booleanCheck;

    /** @var Format\IntegerCheck */
    protected $integerCheck;

    /** @var Format\NumberCheck */
    protected $numberCheck;

    /** @var Format\StringCheck */
    protected $stringCheck;

    public function __construct()
    {
        $this->booleanCheck = new Format\BooleanCheck;
        $this->integerCheck = new Format\IntegerCheck;
        $this->numberCheck = new Format\NumberCheck;
        $this->stringCheck = new Format\StringCheck;
    }

    /**
     * @param array $params
     */
    public function checkParams(array $params)
    {
        $validationErrors = [];

        foreach ($params as $param) {
            try {
                $this->checkParam($param);
            } catch (ValidationException $e) {
                array_push($validationErrors, $e);
            }
        }

        // todo bubble up the errors
        if (count($validationErrors) > 0) {
            throw new HttpError\BadRequest('Bad parameters passed in request');
        }
    }

    /**
     * @param array $param
     */
    protected function checkParam(array $param)
    {
        $this->checkRequired($param);

        // todo if empty, bail
        // todo formdata

        if ($param['in'] === 'body') {
            $this->checkBodySchema($param);
            return;
        }

        $this->checkParamValue($param);
    }

    /**
     * @param array $param
     */
    protected function checkRequired(array $param)
    {
        if (!isset($param['required']) || $param['required'] === false) {
            return;
        }

        if (!isset($param['value'])) {
            throw new ValidationException('Required value was not set');
        }
    }

    /**
     * @param array $param
     */
    protected function checkBodySchema(array $param)
    {
        $bodyParam = array_merge(
            $param['schema'],
            [
                'value' => $param['value'],
            ]
        );

        return $this->checkParamValue($bodyParam);
    }

    /**
     * @param array $param
     */
    protected function checkParamValue(array $param)
    {
        if ($param['type'] === 'array') {
            $this->checkItems($param);

            $self = $this;
            return array_walk(
                $param['value'],
                function ($value) use ($self, $param) {
                    $itemParam = array_merge(
                        $param['items'],
                        [
                            'value' => $value,
                        ]
                    );
                    $self->checkParamValue($itemParam);
                }
            );
        }

        if ($param['type'] === 'object') {
            $this->checkRequiredProperties($param);

            $self = $this;
            return array_walk(
                $param['properties'],
                function ($schema, $key) use ($self, $param) {
                    if (!isset($param['value'][$key])) {
                        return;
                    }

                    $propertyParam = array_merge(
                        $schema,
                        [
                            'value' => $param['value'][$key],
                        ]
                    );
                    $self->checkParamValue($propertyParam);
                }
            );
        }

        $this->checkFormat($param);
    }

    /**
     * @param array $param
     */
    protected function checkItems(array $param)
    {
        if (isset($param['maxItems']) && $param['maxItems'] < count($param['value'])) {
            throw new ValidationException('Size of array exceeds maxItems');
        }
        if (isset($param['minItems']) && $param['minItems'] > count($param['value'])) {
            throw new ValidationException('Size of array exceeds minItems');
        }
        if (isset($param['uniqueItems']) && $param['uniqueItems'] == true) {
            $uniqueValues = array_unique($param['value']);
            if (count($uniqueValues) < count($param['value'])) {
                throw new ValidationException('Duplicate array items found when should be unique');
            }
        }
    }

    /**
     * @param array $param
     */
    protected function checkRequiredProperties(array $param)
    {
        $requiredProperties = [];
        if (array_key_exists('required', $param)) {
            $requiredProperties = $param['required'];
        }

        $properties = array_keys($param['value']);
        foreach($requiredProperties as $requiredProperty) {
            if (in_array($requiredProperty, $properties)) {
                continue;
            }

            throw new ValidationException('Required value was not set');
        }
    }

    /**
     * @param array $param
     */
    protected function checkFormat(array $param)
    {
        // todo move this check up to checkParam
        if (strlen($param['value']) < 1) {
            return;
        }

        if ($param['type'] === 'boolean') {
            $this->booleanCheck->check($param);
            return;
        }
        if ($param['type'] === 'integer') {
            $this->integerCheck->check($param);
            return;
        }
        if ($param['type'] === 'number') {
            $this->numberCheck->check($param);
            return;
        }
        if ($param['type'] === 'string') {
            $this->stringCheck->check($param);
            return;
        }
    }
}
