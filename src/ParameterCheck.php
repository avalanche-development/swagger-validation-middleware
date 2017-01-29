<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use Psr\Http\Message\RequestInterface;

class ParameterCheck
{

    /**
     * @param RequestInterface $request
     * @param array $params
     */
    public function checkParams(RequestInterface $request, array $params)
    {
        $self = $this;
        $isValid = array_reduce(
            $params,
            function ($result, $param) use ($self, $request) {
                return ($self->checkParam($request, $param) && $result);
            },
            true
        );

        if (!$isValid) {
            throw new HttpError\BadRequest('Bad parameters passed in request');
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkParam(RequestInterface $request, array $param)
    {
        switch ($param['in']) {
            case 'body':
                $result = $this->checkBodyParam($request, $param);
                break;
            case 'formData':
                $result = $this->checkFormParam($request, $param);
                break;
            case 'header':
                $result = $this->checkHeaderParam($request, $param);
                break;
            case 'path':
                $result = $this->checkPathParam($request, $param);
                break;
            case 'query':
                $result = $this->checkQueryParam($request, $param);
                break;
            default:
                throw new \Exception('Invalid location set for parameter');
                break;
        }

        return $result;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkBodyParam(RequestInterface $request, array $param)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkFormParam(RequestInterface $request, array $param)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkHeaderParam(RequestInterface $request, array $param)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkPathParam(RequestInterface $request, array $param)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkQueryParam(RequestInterface $request, array $param)
    {
        return true;
    }
}
