<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the RouteParameters DTO
 */
final class RouteParametersTest extends UnitTestCase
{
    public static function withParameterThrowsExceptionForInvalidParameterValuesDataProvider(): \Iterator
    {
        yield ['parameterValue' => new \stdClass()];
        yield ['parameterValue' => $this->getMockBuilder(RouterInterface::class)->getMock()];
        yield ['parameterValue' => null];
    }

    /**
     * @test
     * @dataProvider withParameterThrowsExceptionForInvalidParameterValuesDataProvider
     */
    public function withParameterThrowsExceptionForInvalidParameterValues($parameterValue)
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteParameters::createEmpty()->withParameter('someParameter', $parameterValue);
    }

    public function withParameterAcceptsValidParameterValuesDataProvider(): \Iterator
    {
        yield ['parameterValue' => 'string'];
        yield ['parameterValue' => 123];
        yield ['parameterValue' => 123.45];
        yield ['parameterValue' => true];
        yield ['parameterValue' => false];
        yield ['parameterValue' => $this->createStub(CacheAwareInterface::class)];
    }

    /**
     * @test
     * @dataProvider withParameterAcceptsValidParameterValuesDataProvider
     */
    public function withParameterAcceptsValidParameterValues($parameterValue)
    {
        RouteParameters::createEmpty()->withParameter('someParameter', $parameterValue);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function withParameterDoesNotMutateTheObject()
    {
        $originalParameters = RouteParameters::createEmpty();
        $originalParameters->withParameter('someParameter', 'someValue');
        self::assertFalse($originalParameters->has('someParameter'));
    }

    /**
     * @test
     */
    public function withParameterReturnsANewInstanceWithTheGivenParameter()
    {
        $originalParameters = RouteParameters::createEmpty()->withParameter('someParameter', 'someValue');
        self::assertSame('someValue', $originalParameters->getValue('someParameter'));
    }

    /**
     * @test
     */
    public function withParameterOverridesAnyPreviousParameters()
    {
        $originalParameters = RouteParameters::createEmpty()->withParameter('someParameter', 'someValue');
        $originalParameters = $originalParameters->withParameter('someParameter', 'overriddenValue');
        self::assertSame('overriddenValue', $originalParameters->getValue('someParameter'));
    }
}
