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

    /** @var HeaderCheck */
    protected $headerCheck;

    public function __construct()
    {
        $this->headerCheck = new HeaderCheck;

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

        $securityCheck = new SecurityCheck($request);

        $security = $request->getAttribute('swagger')['security'];
        if (!$this->checkSecurity($securityCheck, $security)) {
            throw new HttpError\Unauthorized('Unacceptable security passed in request');
        }

        $schemes = $request->getAttribute('swagger')['schemes'];
        if (!$this->checkScheme($request, $schemes)) {
            throw new HttpError\NotFound('Unallowed scheme in request');
        }

        $consumeHeaders = $request->getAttribute('swagger')['consumes'];
        if (!$this->headerCheck->checkIncomingContent($request, $consumeHeaders)) {
            throw new HttpError\NotAcceptable('Unacceptable header was passed into this endpoint');
        }

        // todo check parameters

        $result = $next($request, $response);

        $produceHeaders = $request->getAttribute('swagger')['produces'];
        if (!$this->headerCheck->checkOutgoingContent($result, $produceHeaders)) {
            throw new HttpError\InternalServerError('Invalid content detected');
        }
        if (!$this->headerCheck->checkAcceptHeader($request, $result)) {
            throw new HttpError\NotAcceptable('Unacceptable content detected');
        }

        // todo check response body

        return $result;
    }

    /**
     * @param SecurityCheck $securityCheck
     * @param array $security
     * @return boolean
     */
    public function checkSecurity(SecurityCheck $securityCheck, array $security)
    {
        $metSecurity = array_filter($security, [$securityCheck, 'checkScheme']);
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
