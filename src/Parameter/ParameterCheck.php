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
        return;
    }

    /**
     * @param array $param
     */
    protected function checkParamValue(array $param)
    {
        if ($param['type'] === 'array') {
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

        $this->checkFormat($param);
    }

    /**
     * @param array $param
     */
    protected function checkFormat(array $param)
    {
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
