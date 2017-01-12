<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HeaderCheck
{

    /**
     * @param RequestInterface $request
     * @param array $consumeTypes
     * @return boolean
     */
    public function checkIncomingContent(RequestInterface $request, array $consumeTypes)
    {
        if (!$request->getBody()->getSize()) {
            return true;
        }

        return $this->checkMessageContent($request, $consumeTypes);
    }

    /**
     * @param ResponseInterface $response
     * @param array $produceTypes
     * @return Response
     */
    public function checkOutgoingContent(ResponseInterface $response, array $produceTypes)
    {
        if (empty($response->getHeader('content-type'))) {
            return true;
        }

        return $this->checkMessageContent($response, $produceTypes);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return boolean
     */
    public function checkAcceptHeader(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($request->getHeader('accept'))) {
            return true;
        }

        $acceptTypes = $request->getHeader('accept');
        return $this->checkMessageContent($response, $acceptTypes);
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
