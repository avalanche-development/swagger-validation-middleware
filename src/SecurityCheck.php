<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use Psr\Http\Message\RequestInterface;

class SecurityCheck
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
            return $this->checkBasicScheme();
        } elseif ($scheme['type'] === 'oauth') {
            return $this->checkOAuthScheme($scheme);
        }
        return false;
    }

    /**
     * @return boolean
     */
    protected function checkBasicScheme()
    {
        $authHeader = $this->request->getHeader('Authorization');
        $authHeader = explode(' ', $authHeader);
        return ($authHeader[0] === 'Basic' && preg_match('/^[a-z0-9]+$/i', $authHeader[1]) === 1);
    }

    /**
     * @param array $scheme
     * @return boolean
     */
    protected function checkOauthScheme(array $scheme)
    {
        throw new \Exception('OAuth is not yet implemented');
    }
}
