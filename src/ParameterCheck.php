<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;

class ParameterCheck
{

    /**
     * @param array $params
     */
    public function checkParams(array $params)
    {
        $self = $this;
        $isValid = array_reduce(
            $params,
            function ($result, $param) use ($self) {
                return ($self->checkParam($param) && $result);
            },
            true
        );

        if (!$isValid) {
            throw new HttpError\BadRequest('Bad parameters passed in request');
        }
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkParam(array $param)
    {
        if (!$this->checkRequired($param)) {
            return false;
        }

        if ($param['in'] === 'body') {
            return $this->checkBodySchema($param);
        }

        return $this->checkParamValue($param);
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkRequired(array $param)
    {
        if (!isset($param['required']) || $param['required'] === false) {
            return true;
        }

        return isset($param['value']);
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkBodySchema(array $param)
    {
        return true;
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
            return $this->checkIntegerFormat($param);
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
    protected function checkIntegerFormat(array $param)
    {
        if (!is_int($param['value'])) {
            return false;
        }
        if (!isset($param['format'])) {
            return true;
        }

        if ($param['format'] === 'int32' && (
            $param['value'] < -2147483647 || $param['value'] > -2147483647
        )) {
            return false;
        }
        if ($param['format'] === 'int64' && (
            $param['value'] < -9223372036854775807 || $param['value'] > 9223372036854775807
        )) {
            return false;
        } 

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
