<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\ObjectManagement;

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
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithLazyDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassB;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassC;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\AnotherClassWithLazyDependencies;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Lazy Dependency Injection features
 *
 */
final class LazyDependencyInjectionTest extends FunctionalTestCase
{
    #[Test]
    public function lazyDependencyIsOnlyInjectedIfMethodOnDependencyIsCalledForTheFirstTime()
    {
        $this->objectManager->forgetInstance(SingletonClassA::class);

        $object = $this->objectManager->get(ClassWithLazyDependencies::class);
        self::assertInstanceOf(DependencyProxy::class, $object->lazyA);

        $actualObjectB = $object->lazyA->getObjectB();
        $this->assertNotInstanceOf(DependencyProxy::class, $object->lazyA);

        $objectA = $this->objectManager->get(SingletonClassA::class);
        $expectedObjectB = $this->objectManager->get(SingletonClassB::class);
        self::assertSame($objectA, $object->lazyA);
        self::assertSame($expectedObjectB, $actualObjectB);
    }

    #[Test]
    public function dependencyIsInjectedDirectlyIfLazyIsTurnedOff()
    {
        $object = $this->objectManager->get(ClassWithLazyDependencies::class);
        self::assertInstanceOf(SingletonClassC::class, $object->eagerC);
    }

    #[Test]
    public function lazyDependencyIsInjectedIntoAllClassesWhichNeedItIfItIsUsedTheFirstTime()
    {
        $this->objectManager->forgetInstance(SingletonClassA::class);
        $this->objectManager->forgetInstance(SingletonClassB::class);

        $object1 = $this->objectManager->get(ClassWithLazyDependencies::class);
        $object2 = $this->objectManager->get(AnotherClassWithLazyDependencies::class);

        self::assertInstanceOf(DependencyProxy::class, $object1->lazyA);
        self::assertInstanceOf(DependencyProxy::class, $object2->lazyA);

        $object2->lazyA->getObjectB();

        $objectA = $this->objectManager->get(SingletonClassA::class);
        self::assertSame($objectA, $object1->lazyA);
        self::assertSame($objectA, $object2->lazyA);
    }
}
