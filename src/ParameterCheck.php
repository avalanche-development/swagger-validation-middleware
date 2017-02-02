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

        switch ($param['in']) {
            case 'body':
                $result = $this->checkBodyParam($param);
                break;
            case 'formData':
                $result = $this->checkFormParam($param);
                break;
            case 'header':
                $result = $this->checkHeaderParam($param);
                break;
            case 'path':
                $result = $this->checkPathParam($param);
                break;
            case 'query':
                $result = $this->checkQueryParam($param);
                break;
            default:
                throw new \Exception('Invalid location set for parameter');
                break;
        }

        return $result;
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
    protected function checkBodyParam(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkFormParam(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkHeaderParam(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkPathParam(array $param)
    {
        return true;
    }

    /**
     * @param array $param
     * @return boolean
     */
    protected function checkQueryParam(array $param)
    {
        return true;
    }
}
