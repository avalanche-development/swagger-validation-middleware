<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HeaderCheck
{

    /**
     * @param RequestInterface $request
     * @param array $consumeTypes
     */
    public function checkIncomingContent(RequestInterface $request, array $consumeTypes)
    {
        if (!$request->getBody()->getSize()) {
            return;
        }

        if (!$this->checkMessageContent($request, $consumeTypes)) {
            throw new HttpError\NotAcceptable('Unacceptable header was passed into this endpoint');
        }
    }

    /**
     * @param ResponseInterface $response
     * @param array $produceTypes
     */
    public function checkOutgoingContent(ResponseInterface $response, array $produceTypes)
    {
        if (empty($response->getHeader('content-type'))) {
            return;
        }

        if (!$this->checkMessageContent($response, $produceTypes)) {
            throw new HttpError\InternalServerError('Invalid content detected');
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function checkAcceptHeader(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($request->getHeader('accept'))) {
            return;
        }

        $acceptTypes = $request->getHeader('accept');
        if (!$this->checkMessageContent($response, $acceptTypes)) {
            throw new HttpError\NotAcceptable('Unacceptable content detected');
        }
    }

    /**
     * @param MessageInterface $message
     * @param array $acceptableTypes
     * @return boolean
     */
    protected function checkMessageContent(MessageInterface $message, array $acceptableTypes)
    {
        $contentType = $this->extractContentHeader($message);
        foreach ($acceptableTypes as $type) {
            if (in_array($type, $contentType)) {
                return true;
            }
            // todo wildcard mime type matching
        }

        return false;
    }

    /**
     * @param MessageInterface $message
     * @return array
     */
    protected function extractContentHeader(MessageInterface $message)
    {
        $contentHeaders = $message->getHeader('content-type');
        $contentHeaders = current($contentHeaders);
        $contentHeaders = explode(',', $contentHeaders);
        $contentHeaders = array_map(function ($entity) {
            $entity = explode(';', $entity);
            $entity = current($entity);
            return strtolower($entity);
        }, $contentHeaders);

        return $contentHeaders;
    }
}
