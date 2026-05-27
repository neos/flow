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

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\ViewConfigurationManager;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Eel\CompilingEvaluator;

/**
 * Testcase for the MVC ViewConfigurationManager
 *
 */
final class ViewConfigurationManagerTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var ViewConfigurationManager
     */
    protected $viewConfigurationManager;

    /**
     * @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConfigurationManager;


    protected function setUp(): void
    {
        $this->viewConfigurationManager = new ViewConfigurationManager();

        // eel evaluator
        $eelEvaluator = $this->createEvaluator();
        $this->inject($this->viewConfigurationManager, 'eelEvaluator', $eelEvaluator);

        // a dummy configuration manager is prepared
        $this->mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $this->inject($this->viewConfigurationManager, 'configurationManager', $this->mockConfigurationManager);

        // caching is deactivated
        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->method('get')->willReturn((false));
        $this->inject($this->viewConfigurationManager, 'cache', $mockCache);

        // a dummy request is prepared
        $this->mockActionRequest = $this->createMock(ActionRequest::class);
        $this->mockActionRequest->method('getControllerPackageKey')->willReturn(('Neos.Flow'));
        $this->mockActionRequest->method('getControllerSubpackageKey')->willReturn((''));
        $this->mockActionRequest->method('getControllerName')->willReturn(('Standard'));
        $this->mockActionRequest->method('getControllerActionName')->willReturn(('index'));
        $this->mockActionRequest->method('getFormat')->willReturn(('html'));
        $this->mockActionRequest->method('getParentRequest')->willReturn((null));
    }

    /**
     * @test
     */
    public function getViewConfigurationFindsMatchingConfigurationForRequest()
    {
        $matchingConfiguration = [
            'requestFilter' => 'isPackage("Neos.Flow")',
            'options' => 'a value'
        ];

        $notMatchingConfiguration = [
            'requestFilter' => 'isPackage("Vendor.Package")',
            'options' => 'another value'
        ];

        $viewConfigurations = [$notMatchingConfiguration, $matchingConfiguration];

        $this->mockConfigurationManager->method('getConfiguration')->with('Views')->willReturn(($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        self::assertEquals($calculatedConfiguration, $matchingConfiguration);
    }

    /**
     * @test
     */
    public function getViewConfigurationUsedFilterConfigurationWithHigherWeight()
    {
        $matchingConfigurationOne = [
            'requestFilter' => 'isPackage("Neos.Flow")',
            'options' => 'a value'
        ];

        $matchingConfigurationTwo = [
            'requestFilter' => 'isPackage("Neos.Flow") && isFormat("html")',
            'options' => 'a value with higher weight'
        ];

        $notMatchingConfiguration = [
            'requestFilter' => 'isPackage("Vendor.Package")',
            'options' => 'another value'
        ];

        $viewConfigurations = [$notMatchingConfiguration, $matchingConfigurationOne, $matchingConfigurationTwo];

        $this->mockConfigurationManager->method('getConfiguration')->with('Views')->willReturn(($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        self::assertEquals($calculatedConfiguration, $matchingConfigurationTwo);
    }

    /**
     * @return CompilingEvaluator
     */
    protected function createEvaluator()
    {
        $stringFrontendMock = $this->createMock(StringFrontend::class);
        $stringFrontendMock->method('get')->willReturn(false);

        $evaluator = new CompilingEvaluator();
        $evaluator->injectExpressionCache($stringFrontendMock);
        return $evaluator;
    }
}
