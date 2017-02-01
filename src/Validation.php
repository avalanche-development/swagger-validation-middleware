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

    /** @var ParameterCheck */
    protected $parameterCheck;

    /** @var SecurityCheck */
    protected $securityCheck;

    public function __construct()
    {
        $this->headerCheck = new HeaderCheck;
        $this->parameterCheck = new ParameterCheck;
        $this->securityCheck = new SecurityCheck;

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

        $security = $request->getAttribute('swagger')->getSecurity();
        $this->securityCheck->checkSecurity($request, $security);

        $schemes = $request->getAttribute('swagger')->getSchemes();
        $this->checkScheme($request, $schemes);

        $consumeHeaders = $request->getAttribute('swagger')->getConsumes();
        $this->headerCheck->checkIncomingContent($request, $consumeHeaders);

        $params = $request->getAttribute('swagger')->getParams();
        $this->parameterCheck->checkParams($params);

        $result = $next($request, $response);

        $produceHeaders = $request->getAttribute('swagger')->getProduces();
        $this->headerCheck->checkOutgoingContent($result, $produceHeaders);
        $this->headerCheck->checkAcceptHeader($request, $result);

        // todo check response body

        return $result;
    }

    /**
     * @param RequestInterface $request
     * @param array $schemes
     */
    protected function checkScheme(RequestInterface $request, array $schemes)
    {
        $requestScheme = $request->getUri()->getScheme();
        if (!in_array($requestScheme, $schemes)) {
            throw new HttpError\NotFound("Unallowed scheme ({$requestScheme}) in request");
        }
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-validation-middleware: {$message}");
    }
}
