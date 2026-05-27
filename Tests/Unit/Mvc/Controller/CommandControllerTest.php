<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\CommandManager;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Cli\Request;
use Neos\Flow\Cli\Response;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;

/**
 * Testcase for the Command Controller
 */
final class CommandControllerTest extends UnitTestCase
{
    /**
     * @var CommandController
     */
    protected $commandController;

    /**
     * @var ConsoleOutput|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConsoleOutput;

    protected function setUp(): void
    {
        $this->commandController = $this->getAccessibleMock(CommandController::class, ['resolveCommandMethodName', 'callCommandMethod']);

        $mockCommandManager = $this->createMock(CommandManager::class);
        $mockCommandManager->method('getCommandMethodParameters')->willReturn(([]));
        $this->inject($this->commandController, 'commandManager', $mockCommandManager);

        $this->mockConsoleOutput = $this->createMock(ConsoleOutput::class);
        $this->inject($this->commandController, 'output', $this->mockConsoleOutput);
    }


    /**
     * @test
     */
    public function processRequestThrowsExceptionIfGivenRequestIsNoCliRequest()
    {
        $this->expectException(\Error::class);
        $mockRequest = $this->createStub(Mvc\ActionRequest::class);
        $mockResponse = new Mvc\ActionResponse();

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestMarksRequestDispatched()
    {
        $mockRequest = $this->createMock(Request::class);
        $mockResponse = $this->createStub(Response::class);

        $mockRequest->expects($this->once())->method('setDispatched')->with(true);

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestResetsCommandMethodArguments()
    {
        $mockRequest = $this->createStub(Request::class);
        $mockResponse = $this->createStub(Response::class);

        $mockArguments = new Arguments();
        $mockArguments->addNewArgument('foo');
        $this->inject($this->commandController, 'arguments', $mockArguments);

        self::assertCount(1, $this->commandController->_get('arguments'));
        $this->commandController->processRequest($mockRequest, $mockResponse);
        self::assertCount(0, $this->commandController->_get('arguments'));
    }

    /**
     * @test
     */
    public function outputWritesGivenStringToTheConsoleOutput()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('some text');
        $this->commandController->_call('output', 'some text');
    }

    /**
     * @test
     */
    public function outputReplacesArgumentsInGivenString()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('%2$s %1$s', ['text', 'some']);
        $this->commandController->_call('output', '%2$s %1$s', ['text', 'some']);
    }
}
