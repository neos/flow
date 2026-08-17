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
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithDocComments;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithPhpAttributes;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassB;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassE;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassEsub;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassF;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\FinalClassWithDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ReadonlyClassWithDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithKeywordsInClassBody;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SampleAttribute;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\ClassWithConstructorProperties;
use Neos\Flow\Annotations\Around;
use Neos\Flow\Annotations\Session;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassExtendingClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\ClassWithUnionTypes;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\BackedEnumWithMethod;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassK;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SampleMethodAttribute;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Proxy Compiler and related features
 */
final class ProxyCompilerTest extends FunctionalTestCase
{
    /**
     * Make sure that we are actually testing proxy classes and not the
     * original PHP class.
     */
    #[Test]
    public function classWithUnionTypesIsProxied(): void
    {
        $object = new ClassWithUnionTypes();
        self::assertInstanceOf(ProxyInterface::class, $object);
    }

    #[Test]
    public function proxyClassesStillContainAnnotationsFromItsOriginalClass(): void
    {
        $class = new ClassReflection(PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertTrue($class->implementsInterface(ProxyInterface::class));
        self::assertTrue($class->isTaggedWith('scope'));
        self::assertTrue($method->isTaggedWith('session'));
    }

    #[Test]
    public function proxyClassesStillContainDocCommentsFromItsOriginalClass(): void
    {
        $class = new ClassReflection(ClassWithDocComments::class);
        $expectedResult = 'This is a example doc comment which should be copied' . chr(10) . 'to the proxy class.';
        $actualResult = $class->getDescription();

        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function proxiedMethodsStillContainReturnAnnotationFromOriginalClass(): void
    {
        $class = new ClassReflection(PrototypeClassA::class);
        $method = $class->getMethod('getSingletonA');

        self::assertEquals(['SingletonClassA The singleton class A'], $method->getTagValues('return'));
    }

    #[Test]
    public function proxiedMethodsStillContainParamDocumentationFromOriginalClass(): void
    {
        $class = new ClassReflection(PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['string $someProperty The property value'], $method->getTagValues('param'));
    }

    #[Test]
    public function proxiedMethodsDoContainAnnotationsOnlyOnce(): void
    {
        $class = new ClassReflection(PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['autoStart=true'], $method->getTagValues('session'));
    }

    #[Test]
    public function proxiedMethodsStillContainMethodAttributesFromOriginalClass(): void
    {
        $class = new ClassReflection(ClassWithPhpAttributes::class);
        $actualAttributes = [];
        foreach ($class->getMethod('methodWithAttributes')->getAttributes() as $attribute) {
            $actualAttributes[] = [
                'name' => $attribute->getName(),
                'arguments' => $attribute->getArguments(),
            ];
        }
        $expectedAttributes = [
            [
                'name' => Around::class,
                'arguments' => ['pointcutExpression' => 'method(somethingImpossible())']
            ],
            [
                'name' => Session::class,
                'arguments' => ['autoStart' => false]
            ],
            [
                'name' => SampleMethodAttribute::class,
                'arguments' => ['value without name']
            ],
        ];
        self::assertEquals($expectedAttributes, $actualAttributes);
    }

    #[Test]
    public function classesAnnotatedWithProxyDisableAreNotProxied(): void
    {
        $singletonB = $this->objectManager->get(SingletonClassB::class);
        $this->assertNotInstanceOf(ProxyInterface::class, $singletonB);
    }

    /**
     * This test would fail with a fatal error, if Flow would try to build a proxy class for the given Enum:
     *
     * PHP Fatal error:  Cannot declare class Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\BackedEnumWithMethod,
     * because the name is already in use in …/Flow_Object_Classes/Neos_Flow_Tests_Functional_ObjectManagement_Fixtures_PHP8_BackedEnumWithMethod.php on line 47
     */
    #[Test]
    public function enumsAreNotProxied(): void
    {
        # PHP < 8.1 would fail compiling this test case if we used the syntax BackedEnumWithMethod::ESPRESSO->label()
        $this->assertSame('Espresso', BackedEnumWithMethod::getLabel(BackedEnumWithMethod::ESPRESSO));
    }

    #[Test]
    public function setInstanceOfSubClassDoesNotOverrideParentClass(): void
    {
        $singletonE = $this->objectManager->get(SingletonClassE::class);
        self::assertInstanceOf(SingletonClassE::class, $singletonE);

        $singletonEsub = $this->objectManager->get(SingletonClassEsub::class);
        self::assertInstanceOf(SingletonClassEsub::class, $singletonEsub);

        $singletonE2 = $this->objectManager->get(SingletonClassE::class);
        self::assertInstanceOf(SingletonClassE::class, $singletonE2);
        self::assertSame($singletonE, $singletonE2);
    }

    /**
     * @noinspection SuspiciousAssignmentsInspection
     */
    #[Test]
    public function transientPropertiesAreNotSerializedOnSleep(): void
    {
        $prototypeF = $this->objectManager->get(PrototypeClassF::class);
        $prototypeF->setTransientProperty('foo');
        $prototypeF->setNonTransientProperty('bar');

        $serializedObject = serialize($prototypeF);
        $prototypeF = null;

        $prototypeF = unserialize($serializedObject);
        self::assertSame('bar', $prototypeF->getNonTransientProperty());
        self::assertNull($prototypeF->getTransientProperty());
    }

    #[Test]
    public function proxiedFinalClassesAreStillFinal(): void
    {
        $reflectionClass = new ClassReflection(FinalClassWithDependencies::class);
        self::assertTrue($reflectionClass->isFinal());
    }

    #[Test]
    public function proxiedReadonlyClassesAreStillReadonly(): void
    {
        $reflectionClass = new ClassReflection(ReadonlyClassWithDependencies::class);
        self::assertTrue($reflectionClass->isReadOnly());
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/1835
     */
    #[Test]
    public function classKeywordIsIgnoredInsideClassBody(): void
    {
        $reflectionClass = new ClassReflection(ClassWithKeywordsInClassBody::class);
        self::assertSame(ClassWithKeywordsInClassBody::class, $reflectionClass->getNamespaceName() . '\ClassWithKeywordsInClassBody');
    }

    #[Test]
    public function attributesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(ClassWithPhpAttributes::class);
        $attributes = $reflectionClass->getAttributes();
        self::assertCount(2, $attributes);
        self::assertSame(SampleAttribute::class, $attributes[0]->getName());
        self::assertEquals(ClassWithPhpAttributes::class, $attributes[0]->getArguments()[0]);
    }

    public function complexPropertyTypesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(ClassWithUnionTypes::class);

        foreach ($reflectionClass->getProperties() as $property) {
            $this->assertInstanceOf(PropertyReflection::class, $property);
            if (
                $property->getName() !== 'classA' &&
                $property->getName() !== 'propertyA' &&
                $property->getName() !== 'propertyB' &&
                !str_starts_with($property->getName(), 'Flow_')
            ) {
                self::assertInstanceOf(\ReflectionUnionType::class, $property->getType(), sprintf('Property "%s" is of type "%s"', $property->getName(), $property->getType()));
            }
        }
        self::assertEquals(
            $reflectionClass->getProperty('propertyA')->getType(),
            $reflectionClass->getProperty('propertyB')->getType(),
            '?string is equal to string|null'
        );
    }

    #[Test]
    public function complexMethodReturnTypesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(ClassWithUnionTypes::class);
        foreach ($reflectionClass->getMethods() as $method) {
            if (str_starts_with($method->getName(), 'get') &&
                !str_ends_with($method->getName(), 'PropertyA') &&
                !str_ends_with($method->getName(), 'PropertyB')) {
                self::assertInstanceOf(\ReflectionUnionType::class, $method->getReturnType(), $method->getName() . ': ' . $method->getReturnType());
            }
        }
        self::assertEquals(
            $reflectionClass->getMethod('getPropertyA')->getReturnType(),
            $reflectionClass->getMethod('getPropertyB')->getReturnType(),
            '?string is equal to string|null'
        );
    }

    /**
     * @throws
     */
    #[Test]
    public function complexMethodParametersArePreserved(): void
    {
        $proxyClassReflection = new ClassReflection(ClassWithUnionTypes::class);
        $originalClassReflection = new ClassReflection(get_parent_class(ClassWithUnionTypes::class));

        $proxyMethodReflection = $proxyClassReflection->getMethod('setPropertyF');
        $originalMethodReflection = $originalClassReflection->getMethod('setPropertyF');

        self::assertEquals(
            $proxyMethodReflection->getParameters()[0]->getType()->getTypes(),
            $originalMethodReflection->getParameters()[0]->getType()->getTypes(),
        );
    }

    #[Test]
    public function constructorPropertiesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(ClassWithConstructorProperties::class);
        /** @var PropertyReflection $property */
        self::assertTrue($reflectionClass->hasProperty('propertyA'));
        self::assertTrue($reflectionClass->hasProperty('propertyB'));
        self::assertTrue($reflectionClass->hasProperty('propertyC'));

        self::assertSame('?string', (string)$reflectionClass->getProperty('propertyA')->getType());
        self::assertSame('?int', (string)$reflectionClass->getProperty('propertyB')->getType());
        self::assertSame('?DateTime', (string)$reflectionClass->getProperty('propertyC')->getType());
    }

    #[Test]
    public function classWithPrivateConstructorCanBeProxied(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInParentClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @noinspection PhpExpressionResultUnusedInspection
     */
    #[Test]
    public function privateConstructorOfProxiedClassCannotBeCalledFromOtherContexts(): void
    {
        $this->expectExceptionCode(1686153840);
        new ClassWithPrivateConstructor('the argument', new PrototypeClassA());
    }

    /**
     * @noinspection UnnecessaryAssertionInspection
     */
    #[Test]
    public function privateConstructorOfProxiedClassCanBeCalledFromProxiedSubClass(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassExtendingClassWithPrivateConstructor::createInSubClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @noinspection UnnecessaryAssertionInspection
     */
    #[Test]
    public function privateConstructorOfProxiedClassCanBeCalledFromAbstractParentClass(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInAbstractClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertNotInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    #[Test]
    public function factoryMethodUsingSelfWorksEvenIfClassIsProxied(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createUsingSelf('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertNotInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);

        $expectedSelves = <<<PHP
            new self();
            self::class;
            self::create();
            function foo(self \$self): self {
                return \$self;
            }
        PHP;
        self::assertSame($expectedSelves, $object->getStringContainingALotOfSelves());
    }

    #[Test]
    public function staticCompileWillResultInAFrozenReturnValue(): void
    {
        $object = new PrototypeClassK();
        self::assertSame($object->getToken(), $object->getToken());
    }
}
