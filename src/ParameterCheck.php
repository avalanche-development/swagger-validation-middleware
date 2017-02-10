<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;

class ParameterCheck
{

    protected $integerCheck;

    public function __construct()
    {
        $this->integerCheck = new Format\IntegerCheck;
    }

    /**
     * @param array $params
     */
    public function checkParams(array $params)
    {
        $validationErrors = [];
        $self = $this;
        array_walk(
            $params,
            function ($param) use ($self) {
                try {
                    $self->checkParam($param);
                } catch (\Exception $e) {
                    array_push($validationErrors, $e);
                }
            }
        );

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
            throw new \Exception('Required value was not set');
        }
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkBodySchema(array $param)
    {
        return;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkParamValue(array $param)
    {
        if ($param['type'] === 'array') {
            if (!$this->checkItems($param)) {
                return false;
            }

            $self = $this;
            return array_reduce(
                $param['items'],
                function ($result, $item) use ($self) {
                    return ($self->checkParamValue($item) && $result);
                },
                true
            );
        }

        if (!$this->checkFormat($param)) {
            return false;
        }
        if ($param['type'] === 'number' && !$this->checkRange($param)) {
            return false;
        }
        if ($param['type'] === 'string' && !$this->checkLength($param)) {
            return false;
        }
        if ($param['type'] === 'string' && !$this->checkPattern($param)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkItems(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkFormat(array $param)
    {
        if (strlen($param['value']) < 1) {
            return true;
        }

        if ($param['type'] === 'boolean') {
            return $this->checkBooleanFormat($param);
        }
        if ($param['type'] === 'integer') {
            return $this->integerCheck->check($param);
        }
        if ($param['type'] === 'number') {
            return $this->checkNumberFormat($param);
        }
        if ($param['type'] === 'string') {
            return $this->checkStringFormat($param);
        }

        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkBooleanFormat(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkNumberFormat(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkStringFormat(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkRange(array $param)
    {
        if (strlen($param['value']) < 1) {
            return true;
        }

        if (isset($param['maximum']) && $param['value'] > $param['maximum']) {
            return false;
        }
        if (isset($param['exclusiveMaximum']) && $param['value'] >= $param['exclusiveMaximum']) {
            return false;
        }
        if (isset($param['minimum']) && $param['value'] < $param['minimum']) {
            return false;
        }
        if (isset($param['exclusiveMinimum']) && $param['value'] <= $param['exclusiveMinimum']) {
            return false;
        }

        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkLength(array $param)
    {
        if (strlen($param['value']) < 1) {
            return true;
        }

        if (isset($param['maxLength']) && strlen($param['value']) > $param['maxLength']) {
            return false;
        }
        if (isset($param['minLength']) && strlen($param['value']) < $param['minLength']) {
            return false;
        }

        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkPattern(array $param)
    {
        if (strlen($param['value']) < 1) {
            return true;
        }
        if (!isset($param['pattern'])) {
            return true;
        }

        return (preg_match("/{$param['pattern']}/", $param['value']) === 1);
    }
}
