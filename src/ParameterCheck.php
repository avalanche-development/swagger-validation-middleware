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
        return true;
    }
}
