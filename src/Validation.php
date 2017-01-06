<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Validation implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface $response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$request->getAttribute('swagger')) {
            $this->log('no swagger information found in request, skipping');
            return $next($request, $response);
        }

        $securityValidation = new SecurityValidation($request);
        $security = $request->getAttribute('swagger')['security'];
        if (!$this->checkSecurity($securityValidation, $security)) {
            throw new HttpError\Unauthorized('Unacceptable security passed in request');
        }

        $schemes = $request->getAttribute('swagger')['schemes'];
        if (!$this->checkScheme($request, $schemes)) {
            throw new HttpError\NotFound('Unallowed scheme in request');
        }

        // todo check header
        // todo check parameters
        $result = $next($request, $response);
        // todo check header
        // todo check response body
        return $result;
    }

    /**
     * @param SecurityValidation $securityValidation
     * @param array $security
     * @return boolean
     */
    public function checkSecurity(SecurityValidation $securityValidation, array $security)
    {
        $metSecurity = array_filter($security, [$securityValidation, 'checkScheme']);
        return count($metSecurity) > 0;
    }

    /**
     * @param RequestInterface $request
     * @param array $schemes
     * @return boolean
     */
    protected function checkScheme(RequestInterface $request, array $schemes)
    {
        $requestScheme = $request->getUri()->getScheme();
        return in_array($requestScheme, $schemes);
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-validation-middleware: {$message}");
    }
}
