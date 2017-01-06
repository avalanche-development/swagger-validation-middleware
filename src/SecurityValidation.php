<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use Psr\Http\Message\RequestInterface;

class SecurityValidation
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @param array $scheme
     * @return boolean
     */
    public function checkScheme(array $scheme)
    {
        if ($scheme['type'] === 'basic') {
            $authHeader = $this->request->getHeader('Authorization');
            $authHeader = explode(' ', $authHeader);
            return ($authHeader[0] === 'Basic' && preg_match('/^[a-z0-9]+$/i', $authHeader[1]) === 1);
        }
        // todo oauth
        return false;
    }
}
