<?php
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
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerInterface;
use Neos\Flow\Mvc\Controller\Exception\InvalidControllerException;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\InfiniteLoopException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\FirewallInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the MVC Dispatcher
 */
class DispatcherTest extends UnitTestCase
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockParentRequest;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockMainRequest;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ControllerInterface|MockObject
     */
    protected $mockController;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Context|MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var FirewallInterface|MockObject
     */
    protected $mockFirewall;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $mockSecurityLogger;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolveController'])
            ->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('isMainRequest')->willReturn(false);

        $this->mockParentRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockParentRequest->method('isMainRequest')->willReturn(true);
        $this->mockActionRequest->method('getParentRequest')->willReturn($this->mockParentRequest);

        $this->mockMainRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->mockMainRequest);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);

        $this->mockController = $this->getMockBuilder(ControllerInterface::class)->setMethods(['processRequest'])->getMock();
        $this->dispatcher->expects(self::any())->method('resolveController')->withAnyParameters()->willReturn($this->mockController);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockFirewall = $this->getMockBuilder(FirewallInterface::class)->getMock();

        $this->mockSecurityLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mockLoggerFactory = $this->getMockBuilder(PsrLoggerFactoryInterface::class)->getMock();
        $mockLoggerFactory->expects(self::any())->method('get')->with('securityLogger')->willReturn($this->mockSecurityLogger);

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->mockObjectManager->method('get')->will(self::returnCallBack(function ($className) use ($mockLoggerFactory) {
            if ($className === PsrLoggerFactoryInterface::class) {
                return $mockLoggerFactory;
            }
            return null;
        }));

        $this->dispatcher->injectObjectManager($this->mockObjectManager);
        $this->dispatcher->injectSecurityContext($this->mockSecurityContext);
        $this->dispatcher->injectFirewall($this->mockFirewall);
    }

    /**
     * @test
     */
    public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests()
    {
        $this->mockController->expects(self::atLeastOnce())->method('processRequest')->will(self::throwException(StopActionException::createForResponse(new Response(), '')));

        $this->dispatcher->dispatch($this->mockParentRequest);
    }

    /**
     * @test
     */
    public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest()
    {
        $this->mockController->expects(self::atLeastOnce())->method('processRequest')->will(self::throwException(StopActionException::createForResponse(new Response(), '')));

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function dispatchContinuesWithNextRequestFoundInAForwardException()
    {
        /** @var ActionRequest|MockObject $nextRequest */
        $nextRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $nextRequest->method('isMainRequest')->willReturn(true);
        $stopException = StopActionException::createForResponse(new Response(), '');
        $forwardException = ForwardException::createForNextRequest($nextRequest, '');

        $this->mockController->expects(self::exactly(2))->method('processRequest')
            ->withConsecutive([$this->mockActionRequest], [$this->mockParentRequest])
            ->willReturnOnConsecutiveCalls(self::throwException($forwardException), self::throwException($stopException));

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations()
    {
        $forwardException = ForwardException::createForNextRequest($this->mockActionRequest, '');

        $this->mockController->expects(self::any())->method('processRequest')->with($this->mockActionRequest)->will(self::throwException($forwardException));

        $this->expectException(InfiniteLoopException::class);

        $this->dispatcher->dispatch($this->mockParentRequest);
    }

    /**
     * @test
     */
    public function dispatchDoesNotBlockRequestsIfAuthorizationChecksAreDisabled()
    {
        $this->mockSecurityContext->method('areAuthorizationChecksDisabled')->willReturn(true);
        $this->mockFirewall->expects(self::never())->method('blockIllegalRequests');
        $this->mockController->expects(self::any())->method('processRequest')->with($this->mockActionRequest)->willReturn(new Response());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function dispatchInterceptsActionRequestsByDefault()
    {
        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->with($this->mockActionRequest);
        $this->mockController->expects(self::any())->method('processRequest')->with($this->mockActionRequest)->willReturn(new Response());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function dispatchThrowsAuthenticationExceptions()
    {
        $this->expectException(AuthenticationRequiredException::class);
        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest')->with($this->mockMainRequest);

        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->will(self::throwException(new AuthenticationRequiredException()));

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function dispatchRethrowsAccessDeniedException()
    {
        $this->expectException(AccessDeniedException::class);
        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->will(self::throwException(new AccessDeniedException()));

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function resolveControllerReturnsTheControllerSpecifiedInTheRequest()
    {
        $mockController = $this->createMock(ControllerInterface::class);

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, null);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface()
    {
        $this->expectException(InvalidControllerException::class);
        $mockController = $this->createMock('stdClass');

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist()
    {
        $this->expectException(InvalidControllerException::class);
        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName', 'getHttpRequest'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('');
        $mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);

        $dispatcher->_call('resolveController', $mockRequest);
    }
}
