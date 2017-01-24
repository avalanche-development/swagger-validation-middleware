<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use Psr\Http\Message\RequestInterface;

class ParameterCheck
{

    /**
     * @param RequestInterface $request
     * @param array $params
     * @return boolean
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
        return $isValid;
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
    protected function checkBodyParam(RequestInterface $request)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkFormParam(RequestInterface $request)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkHeaderParam(RequestInterface $request)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkPathParam(RequestInterface $request)
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @param array $param
     * @return boolean
     */
    protected function checkQueryParam(RequestInterface $request)
    {
        return true;
    }
}
