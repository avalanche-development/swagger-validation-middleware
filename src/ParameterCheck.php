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
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkRange(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkLength(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkPattern(array $param)
    {
        return true;
    }
}
