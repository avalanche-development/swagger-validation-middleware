<?php

namespace AvalancheDevelopment\SwaggerValidationMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;

class HeaderCheckCheckTest extends PHPUnit_Framework_TestCase
{

    public function testCheckIncomingContentReturnsTrueIfEmptyBody()
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(null);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->never())
            ->method('checkMessageContent');

        $result = $headerCheck->checkIncomingContent($mockRequest, []);

        $this->assertTrue($result);
    }

    public function testCheckIncomingContentPassesOnCheckerIfContainsBody()
    {
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn($mockStream);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockRequest, $consumeTypes)
            ->willReturn(true);

        $result = $headerCheck->checkIncomingContent($mockRequest, $consumeTypes);

        $this->assertTrue($result);
    }

    public function testCheckOutgoingContentReturnsTrueIfEmptyHeader()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([]);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->never())
            ->method('checkMessageContent');

        $result = $headerCheck->checkOutgoingContent($mockResponse, []);

        $this->assertTrue($result);
    }

    public function testCheckOutgoingContentPassesOnCheckerIfContainsHeaders()
    {
        $produceTypes = [
            'application/json',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([
                'some value',
            ]);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockResponse, $produceTypes)
            ->willReturn(true);

        $result = $headerCheck->checkOutgoingContent($mockResponse, $produceTypes);

        $this->assertTrue($result);
    }

    public function testCheckAcceptHeaderReturnsTrueIfEmptyHeader()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->never())
            ->method('checkMessageContent');

        $result = $headerCheck->checkAcceptHeader($mockRequest, $mockResponse);

        $this->assertTrue($result);
    }

    public function testCheckAcceptHeaderPassesOnCheckerIfContainsHeaders()
    {
        $expectTypes = [
            'application/json',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->exactly(2))
            ->method('getHeader')
            ->with('accept')
            ->willReturn($expectTypes);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $headerCheck->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockResponse, $expectTypes)
            ->willReturn(true);

        $result = $headerCheck->checkAcceptHeader($mockRequest, $mockResponse);

        $this->assertTrue($result);
    }

    public function testCheckMessageContentReturnsTrueIfPassed()
    {
        $contentHeader = [
            'application/json',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedCheckMessageContent = $reflectedHeaderCheck->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $headerCheck->expects($this->once())
            ->method('extractContentHeader')
            ->with($mockMessage)
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($headerCheck, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMessageContentReturnsFalseIfNotPassed()
    {
        $contentHeader = [];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedCheckMessageContent = $reflectedHeaderCheck->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $headerCheck->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($headerCheck, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckMessageContentReturnsFalseIfNoMatch()
    {
        $contentHeader = [
            'text/plain',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedCheckMessageContent = $reflectedHeaderCheck->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $headerCheck->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($headerCheck, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testExtractContentHeaderHandlesMultipleTypes()
    {
        $contentTypes = 'application/vnd.github+json,application/json';
        $contentTypeCount = 2;

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([
                $contentTypes,
            ]);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedExtractContentHeader = $reflectedHeaderCheck->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedExtractContentHeader->invokeArgs($headerCheck, [
            $mockMessage,
        ]);

        $this->assertCount($contentTypeCount, $result);
    }

    public function testExtractContentHeaderParsesOutOptions()
    {
        $contentType = 'text/plain; charset=utf8';
        $extractedContentHeader = [
            'text/plain',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([
                $contentType,
            ]);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedExtractContentHeader = $reflectedHeaderCheck->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $result = $reflectedExtractContentHeader->invokeArgs($headerCheck, [
            $mockMessage,
        ]);

        $this->assertEquals($extractedContentHeader, $result);
    }

    public function testExtractContentHeaderLowersCasing()
    {
        $contentType = 'Application/Json';
        $casedContentHeader = [
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([
                $contentType,
            ]);

        $reflectedHeaderCheck = new ReflectionClass(HeaderCheck::class);
        $reflectedExtractContentHeader = $reflectedHeaderCheck->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $headerCheck = $this->getMockBuilder(HeaderCheck::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedExtractContentHeader->invokeArgs($headerCheck, [
            $mockMessage,
        ]);

        $this->assertEquals($casedContentHeader, $result);
    }
}
