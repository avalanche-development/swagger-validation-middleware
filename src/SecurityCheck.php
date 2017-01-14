<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use Psr\Http\Message\RequestInterface;

class SecurityCheck
{

    /**
     * @param RequestInterface $request
     * @param array $scheme
     * @return boolean
     */
    public function checkScheme(RequestInterface $request, array $scheme)
    {
        if ($scheme['type'] === 'basic') {
            return $this->checkBasicScheme($request);
        } elseif ($scheme['type'] === 'oauth') {
            return $this->checkOAuthScheme($request, $scheme);
        }
        return false;
    }

    /**
     * @param RequestInterface $request
     * @return boolean
     */
    protected function checkBasicScheme(RequestInterface $request)
    {
        $authHeader = $request->getHeader('Authorization');
        $authHeader = explode(' ', $authHeader);
        return ($authHeader[0] === 'Basic' && preg_match('/^[a-z0-9]+$/i', $authHeader[1]) === 1);
    }

    /**
     * @param RequestInterface $request
     * @param array $scheme
     * @return boolean
     */
    protected function checkOauthScheme(RequestInterface $request, array $scheme)
    {
        throw new \Exception('OAuth is not yet implemented');
    }
}
