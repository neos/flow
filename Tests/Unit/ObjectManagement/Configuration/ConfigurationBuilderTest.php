<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\Configuration\ConfigurationBuilder;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationParser;
use Neos\Flow\ObjectManagement\Exception;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the object configuration builder
 *
 */
class ConfigurationBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function privatePropertyAnnotatedForInjectionThrowsException()
    {
        $this->expectException(Exception::class);
        $configurationArray = [];
        $configurationArray['arguments'][1]['setting'] = 'Neos.Foo.Bar';
        $configurationArray['properties']['someProperty']['setting'] = 'Neos.Bar.Baz';

        $reflectionServiceMock = $this->createMock(ReflectionService::class);
        $reflectionServiceMock
                ->expects(self::once())
                ->method('getPropertyNamesByAnnotation')
                ->with(__CLASS__, Flow\Inject::class)
                ->will(self::returnValue(['dummyProperty']));

        $reflectionServiceMock
                ->expects(self::once())
                ->method('isPropertyPrivate')
                ->with(__CLASS__, 'dummyProperty')
                ->will(self::returnValue(true));

        $lopgerMock = $this->createMock(LoggerInterface::class);

        $configurationBuilder = new ConfigurationBuilder($reflectionServiceMock, new ConfigurationParser($reflectionServiceMock), $lopgerMock);
        $configurationBuilder->buildObjectConfigurations(['Neos.Flow.Testing' => [__CLASS__]], ['Neos.Flow.Testing' => [__CLASS__ => $configurationArray]]);
    }

    /**
     * @test
     */
    public function errorOnGetClassMethodsThrowsException()
    {
        $this->expectException(Exception\UnknownClassException::class);
        $configurationArray = [];
        $configurationArray['properties']['someProperty']['object']['name'] = 'Foo';
        $configurationArray['properties']['someProperty']['object']['className'] = 'foobar';

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $dummyObjectConfiguration = [$configurationBuilder->_call('parseConfigurationArray', 'Foo', $configurationArray, __CLASS__)];

        $configurationBuilder->_callRef('autowireProperties', $dummyObjectConfiguration);
    }

    /**
     * @test
     */
    public function objectsCreatedByFactoryShouldNotFailOnMissingConstructorArguments()
    {
        $configurationArray = [
            'scope' => 'singleton',
            'factoryObjectName' => 'TestFactory',
        ];

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $dummyObjectConfiguration = [$configurationBuilder->_call('parseConfigurationArray', __CLASS__, $configurationArray)];

        $reflectionServiceMock = $this->createMock(ReflectionService::class);

        $reflectionServiceMock
            ->method('hasMethod')
            ->with(__CLASS__, '__construct')
            ->will($this->returnValue(true));

        $reflectionServiceMock
            ->method('getMethodParameters')
            ->with(__CLASS__, '__construct')
            ->will($this->returnValue([
                'testArray' => [
                    'position' => 0,
                    'optional' => false,
                    'class' => null,
                    'allowsNull' => false
                ]
            ]));

        $configurationBuilder->injectReflectionService($reflectionServiceMock);
        try {
            $configurationBuilder->_callRef('autowireArguments', $dummyObjectConfiguration);
        } catch (UnresolvedDependenciesException $e) {
            self::fail('Factory created objects should not throw UnresolvedDependenciesException by autowiring constructor arguments');
        }
        self::assertEquals([], $dummyObjectConfiguration[0]->getArguments());
    }
}
