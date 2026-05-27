<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\DispatchMiddleware;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for the MVC Dispatcher middleware
 */
final class DispatchMiddlewareTest extends UnitTestCase
{
    /**
     * @var DispatchMiddleware
     */
    protected $dispatchMiddleware;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockDispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dispatchMiddleware = new DispatchMiddleware();

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $httpResponse = new Response();
        $this->mockRequestHandler->method('handle')->willReturn($httpResponse);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);
        $this->mockHttpRequest->method('getUploadedFiles')->willReturn([]);

        $this->mockDispatcher = $this->createMock(Dispatcher::class);
        $this->inject($this->dispatchMiddleware, 'dispatcher', $this->mockDispatcher);
        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ACTION_REQUEST)->willReturn($this->createStub(\Neos\Flow\Mvc\ActionRequest::class));
    }

    /**
     * @test
     */
    public function processDispatchesTheRequest()
    {
        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockDispatcher->expects($this->once())->method('dispatch')->with($this->createStub(\Neos\Flow\Mvc\ActionRequest::class));

        $response = $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
