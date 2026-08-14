<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationProperty;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\ObjectManagement\Configuration;

/**
 * Testcase for the object configuration class
 */
final class ConfigurationTest extends UnitTestCase
{
    /**
     * @var Configuration\Configuration
     */
    protected $objectConfiguration;

    /**
     * Prepares everything for a test
     *
     */
    protected function setUp(): void
    {
        $this->objectConfiguration = new Configuration\Configuration('Neos\Foo\Bar');
    }

    /**
     * Checks if setProperties accepts only valid values
     */
    #[Test]
    public function setPropertiesOnlyAcceptsValidValues()
    {
        $this->expectException(InvalidConfigurationException::class);
        $invalidProperties = [
            'validProperty' => new ConfigurationProperty('validProperty', 'simple string'),
            'invalidProperty' => 'foo'
        ];

        $this->objectConfiguration->setProperties($invalidProperties);
    }

    #[Test]
    public function passingAnEmptyArrayToSetPropertiesRemovesAllExistingproperties()
    {
        $someProperties = [
            'prop1' => new ConfigurationProperty('prop1', 'simple string'),
            'prop2' => new ConfigurationProperty('prop2', 'another string')
        ];
        $this->objectConfiguration->setProperties($someProperties);
        self::assertEquals($someProperties, $this->objectConfiguration->getProperties(), 'The set properties could not be retrieved again.');

        $this->objectConfiguration->setProperties([]);
        self::assertEquals([], $this->objectConfiguration->getProperties(), 'The properties have not been cleared.');
    }

    /**
     * Checks if setArguments accepts only valid values
     */
    #[Test]
    public function setArgumentsOnlyAcceptsValidValues()
    {
        $this->expectException(InvalidConfigurationException::class);
        $invalidArguments = [
            1 => new ConfigurationArgument(1, 'simple string'),
            2 => 'foo'
        ];

        $this->objectConfiguration->setArguments($invalidArguments);
    }

    #[Test]
    public function passingAnEmptyArrayToSetArgumentsRemovesAllExistingArguments()
    {
        $someArguments = [
            1 => new ConfigurationArgument(1, 'simple string'),
            2 => new ConfigurationArgument(2, 'another string')
        ];
        $this->objectConfiguration->setArguments($someArguments);
        self::assertEquals($someArguments, $this->objectConfiguration->getArguments(), 'The set arguments could not be retrieved again.');

        $this->objectConfiguration->setArguments([]);
        self::assertEquals([], $this->objectConfiguration->getArguments(), 'The constructor arguments have not been cleared.');
    }

    #[Test]
    public function setFactoryObjectNameAcceptsValidClassNames()
    {
        $this->objectConfiguration->setFactoryObjectName(__CLASS__);
        self::assertSame(__CLASS__, $this->objectConfiguration->getFactoryObjectName());
    }

    #[Test]
    public function setFactoryMethodNameAcceptsValidStrings()
    {
        $this->objectConfiguration->setFactoryMethodName('someMethodName');
        self::assertSame('someMethodName', $this->objectConfiguration->getFactoryMethodName());
    }

    #[Test]
    public function setFactoryMethodNameRejectsAnythingElseThanAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->objectConfiguration->setFactoryMethodName([]);
    }

    #[Test]
    public function theDefaultFactoryMethodNameIsCreate()
    {
        $this->objectConfiguration->setFactoryObjectName(__CLASS__);
        self::assertSame('create', $this->objectConfiguration->getFactoryMethodName());
    }
}
