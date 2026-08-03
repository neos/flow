<?php

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

use Neos\Flow\Annotations\Around;
use Neos\Flow\Annotations\Session;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassExtendingClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithGeneratorMethods;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\ClassWithUnionTypes;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\BackedEnumWithMethod;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\ClassWithFirstClassCallables;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\ClassWithIntersectionTypes;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\IdentifiableAndNameable;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP82\ClassUsingTraitWithConstants;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP83\ClassWithDynamicConstantFetch;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP83\ClassWithOverrideAttribute;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP83\ClassWithTypedConstants;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP83\GreetingProviderInterface;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP84\ClassWithNewWithoutParentheses;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassK;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SampleMethodAttribute;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Proxy Compiler and related features
 */
class ProxyCompilerTest extends FunctionalTestCase
{
    /**
     * Make sure that we are actually testing proxy classes and not the
     * original PHP class.
     *
     * @test
     */
    public function classWithUnionTypesIsProxied(): void
    {
        $object = new ClassWithUnionTypes();
        self::assertInstanceOf(ProxyInterface::class, $object);
    }

    /**
     * @test
     */
    public function proxyClassesStillContainAnnotationsFromItsOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertTrue($class->implementsInterface(ProxyInterface::class));
        self::assertTrue($class->isTaggedWith('scope'));
        self::assertTrue($method->isTaggedWith('session'));
    }

    /**
     * @test
     */
    public function proxyClassesStillContainDocCommentsFromItsOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\ClassWithDocComments::class);
        $expectedResult = 'This is a example doc comment which should be copied' . chr(10) . 'to the proxy class.';
        $actualResult = $class->getDescription();

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainReturnAnnotationFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('getSingletonA');

        self::assertEquals(['SingletonClassA The singleton class A'], $method->getTagValues('return'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainParamDocumentationFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['string $someProperty The property value'], $method->getTagValues('param'));
    }

    /**
     * @test
     */
    public function proxiedMethodsDoContainAnnotationsOnlyOnce(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['autoStart=true'], $method->getTagValues('session'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainMethodAttributesFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\ClassWithPhpAttributes::class);
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

    /**
     * @test
     */
    public function classesAnnotatedWithProxyDisableAreNotProxied(): void
    {
        $singletonB = $this->objectManager->get(Fixtures\SingletonClassB::class);
        $this->assertNotInstanceOf(ProxyInterface::class, $singletonB);
    }

    /**
     * This test would fail with a fatal error, if Flow would try to build a proxy class for the given Enum:
     *
     * PHP Fatal error:  Cannot declare class Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\BackedEnumWithMethod,
     * because the name is already in use in …/Flow_Object_Classes/Neos_Flow_Tests_Functional_ObjectManagement_Fixtures_PHP8_BackedEnumWithMethod.php on line 47
     *
     * @test
     */
    public function enumsAreNotProxied(): void
    {
        # PHP < 8.1 would fail compiling this test case if we used the syntax BackedEnumWithMethod::ESPRESSO->label()
        $this->assertSame('Espresso', BackedEnumWithMethod::getLabel(BackedEnumWithMethod::ESPRESSO));
    }

    /**
     * @test
     */
    public function setInstanceOfSubClassDoesNotOverrideParentClass(): void
    {
        $singletonE = $this->objectManager->get(Fixtures\SingletonClassE::class);
        self::assertEquals(Fixtures\SingletonClassE::class, get_class($singletonE));

        $singletonEsub = $this->objectManager->get(Fixtures\SingletonClassEsub::class);
        self::assertEquals(Fixtures\SingletonClassEsub::class, get_class($singletonEsub));

        $singletonE2 = $this->objectManager->get(Fixtures\SingletonClassE::class);
        self::assertEquals(Fixtures\SingletonClassE::class, get_class($singletonE2));
        self::assertSame($singletonE, $singletonE2);
    }

    /**
     * @test
     * @noinspection SuspiciousAssignmentsInspection
     */
    public function transientPropertiesAreNotSerializedOnSleep(): void
    {
        $prototypeF = $this->objectManager->get(Fixtures\PrototypeClassF::class);
        $prototypeF->setTransientProperty('foo');
        $prototypeF->setNonTransientProperty('bar');

        $serializedObject = serialize($prototypeF);
        $prototypeF = null;

        $prototypeF = unserialize($serializedObject);
        self::assertSame($prototypeF->getNonTransientProperty(), 'bar');
        self::assertNull($prototypeF->getTransientProperty());
    }

    /**
     * @test
     */
    public function proxiedFinalClassesAreStillFinal(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\FinalClassWithDependencies::class);
        self::assertTrue($reflectionClass->isFinal());
    }

    /**
     * @test
     */
    public function proxiedReadonlyClassesAreStillReadonly(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\ReadonlyClassWithDependencies::class);
        self::assertTrue($reflectionClass->isReadOnly());
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/1835
     * @test
     */
    public function classKeywordIsIgnoredInsideClassBody(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\ClassWithKeywordsInClassBody::class);
        self::assertEquals(Fixtures\ClassWithKeywordsInClassBody::class, $reflectionClass->getNamespaceName() . '\ClassWithKeywordsInClassBody');
    }

    /**
     * @test
     */
    public function attributesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\ClassWithPhpAttributes::class);
        $attributes = $reflectionClass->getAttributes();
        self::assertCount(2, $attributes);
        self::assertEquals(Fixtures\SampleAttribute::class, $attributes[0]->getName());
        self::assertEquals(Fixtures\ClassWithPhpAttributes::class, $attributes[0]->getArguments()[0]);
    }

    /**
     * @test
     */
    public function complexPropertyTypesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);

        foreach ($reflectionClass->getProperties() as $property) {
            assert($property instanceof PropertyReflection);
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

    /**
     * @test
     */
    public function complexMethodReturnTypesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);
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
     * @test
     * @throws
     */
    public function complexMethodParametersArePreserved(): void
    {
        $proxyClassReflection = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);
        $originalClassReflection = new ClassReflection(get_parent_class(Fixtures\PHP8\ClassWithUnionTypes::class));

        $proxyMethodReflection = $proxyClassReflection->getMethod('setPropertyF');
        $originalMethodReflection = $originalClassReflection->getMethod('setPropertyF');

        self::assertEquals(
            $proxyMethodReflection->getParameters()[0]->getType()->getTypes(),
            $originalMethodReflection->getParameters()[0]->getType()->getTypes(),
        );
    }

    /**
     * @test
     */
    public function constructorPropertiesArePreserved(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithConstructorProperties::class);
        /** @var PropertyReflection $property */
        self::assertTrue($reflectionClass->hasProperty('propertyA'));
        self::assertTrue($reflectionClass->hasProperty('propertyB'));
        self::assertTrue($reflectionClass->hasProperty('propertyC'));

        self::assertEquals('?string', (string)$reflectionClass->getProperty('propertyA')->getType());
        self::assertEquals('?int', (string)$reflectionClass->getProperty('propertyB')->getType());
        self::assertEquals('?DateTime', (string)$reflectionClass->getProperty('propertyC')->getType());
    }

    /**
     * @test
     */
    public function classWithPrivateConstructorCanBeProxied(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInParentClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @test
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function privateConstructorOfProxiedClassCannotBeCalledFromOtherContexts(): void
    {
        $this->expectExceptionCode(1686153840);
        new ClassWithPrivateConstructor('the argument', new PrototypeClassA());
    }

    /**
     * @test
     * @noinspection UnnecessaryAssertionInspection
     */
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
     * @test
     * @noinspection UnnecessaryAssertionInspection
     */
    public function privateConstructorOfProxiedClassCanBeCalledFromAbstractParentClass(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInAbstractClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertNotInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function staticCompileWillResultInAFrozenReturnValue(): void
    {
        $object = new PrototypeClassK();
        self::assertSame($object->getToken(), $object->getToken());
    }

    /**
     * @test
     */
    public function generatorMethodsOfProxiedClassesCanBeIterated(): void
    {
        $object = $this->objectManager->get(ClassWithGeneratorMethods::class);
        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(SingletonClassA::class, $object->getSingletonA());

        self::assertSame(['item-one', 'item-two'], iterator_to_array($object->generateItems()));
        self::assertSame(['a' => 1, 'b' => 2], iterator_to_array($object->generateKeyedValues()));
    }

    /**
     * @test
     */
    public function generatorMethodsOfProxiedClassesCanDelegateWithYieldFrom(): void
    {
        $object = $this->objectManager->get(ClassWithGeneratorMethods::class);

        // The keys of the delegated generator start at 0 again, therefore they must not be preserved here
        $actualItems = iterator_to_array($object->generateItemsWithDelegation(), false);
        self::assertSame(['item-zero', 'item-one', 'item-two', 'item-three'], $actualItems);
    }

    /**
     * @test
     */
    public function generatorsOfProxiedClassesCanReceiveValuesAndReturnAResult(): void
    {
        $object = $this->objectManager->get(ClassWithGeneratorMethods::class);

        $generator = $object->generateSums();
        self::assertSame(0, $generator->current());

        $generator->send(5);
        self::assertSame(5, $generator->current());

        $generator->send(7);
        self::assertSame(12, $generator->current());

        $generator->send(null);
        self::assertFalse($generator->valid());
        self::assertSame(12, $generator->getReturn());
    }

    /**
     * @test
     */
    public function firstClassCallablesCanBeCreatedFromMethodsOfProxiedInstances(): void
    {
        $object = $this->objectManager->get(ClassWithFirstClassCallables::class);
        self::assertInstanceOf(ProxyInterface::class, $object);

        $greeter = $object->greet(...);
        self::assertSame('Hello World', $greeter('World'));
        self::assertSame($object, (new \ReflectionFunction($greeter))->getClosureThis());

        $shouter = ClassWithFirstClassCallables::shout(...);
        self::assertSame('HELLO!', $shouter('hello'));
    }

    /**
     * @test
     */
    public function firstClassCallablesCreatedInsideProxiedClassesCanBeCalled(): void
    {
        $object = $this->objectManager->get(ClassWithFirstClassCallables::class);

        self::assertSame('Hello World', ($object->getGreeterCallable())('World'));
        self::assertSame('psst…', ($object->getWhispererCallable())('PSST'));
        self::assertSame('HELLO!', ($object->getShouterCallable())('hello'));
        self::assertSame(['Hello Alice', 'Hello Bob'], $object->greetAll(['Alice', 'Bob']));
    }

    /**
     * @test
     */
    public function pureIntersectionTypesWorkInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithIntersectionTypes::class);
        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(SingletonClassA::class, $object->getSingletonA());

        $subject = new IdentifiableAndNameable('abc-123', 'Zaphod');
        $object->setSubject($subject);

        self::assertSame($subject, $object->getSubject());
        self::assertSame('abc-123: Zaphod', $object->describe($subject));
    }

    /**
     * @test
     */
    public function pureIntersectionTypesArePreservedInProxiedClasses(): void
    {
        $reflectionClass = new ClassReflection(ClassWithIntersectionTypes::class);

        self::assertInstanceOf(\ReflectionIntersectionType::class, $reflectionClass->getProperty('subject')->getType());
        self::assertInstanceOf(\ReflectionIntersectionType::class, $reflectionClass->getMethod('getSubject')->getReturnType());
        self::assertInstanceOf(\ReflectionIntersectionType::class, $reflectionClass->getMethod('setSubject')->getParameters()[0]->getType());
    }

    /**
     * @test
     */
    public function pureIntersectionTypesAreStillEnforcedInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithIntersectionTypes::class);

        $this->expectException(\TypeError::class);
        /** @noinspection PhpParamsInspection */
        $object->describe(new PrototypeClassA());
    }

    /**
     * @test
     */
    public function typedClassConstantsWorkInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithTypedConstants::class);
        self::assertInstanceOf(ProxyInterface::class, $object);

        self::assertSame(42, ClassWithTypedConstants::ANSWER);
        self::assertSame('Hello', ClassWithTypedConstants::GREETING);

        self::assertSame(42, $object->getAnswer());
        self::assertSame('Hello', $object->getGreeting());
        self::assertSame([1, 2, 3], $object->getNumbers());
    }

    /**
     * @test
     */
    public function typesOfClassConstantsAreVisibleThroughReflectionOnTheProxiedClass(): void
    {
        self::assertSame('int', (string)(new \ReflectionClassConstant(ClassWithTypedConstants::class, 'ANSWER'))->getType());
        self::assertSame('string', (string)(new \ReflectionClassConstant(ClassWithTypedConstants::class, 'GREETING'))->getType());
        self::assertSame('array', (string)(new \ReflectionClassConstant(ClassWithTypedConstants::class, 'NUMBERS'))->getType());
    }

    /**
     * @test
     */
    public function constantsDeclaredInTraitsWorkInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassUsingTraitWithConstants::class);
        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(SingletonClassA::class, $object->getSingletonA());

        self::assertSame('Hello from the trait', ClassUsingTraitWithConstants::GREETING);
        self::assertSame('Hello from the trait', $object->getGreetingFromTrait());
        self::assertSame(42, $object->getAnswerFromTrait());
    }

    /**
     * @test
     */
    public function methodsAnnotatedWithOverrideAttributeWorkInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithOverrideAttribute::class);
        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(GreetingProviderInterface::class, $object);

        self::assertSame('Greetings!', $object->greet());
        self::assertSame('a very polite greeting provider', $object->describe());
    }

    /**
     * @test
     */
    public function overrideAttributeIsStillVisibleThroughReflectionOnTheProxiedClass(): void
    {
        $reflectionClass = new ClassReflection(ClassWithOverrideAttribute::class);

        self::assertCount(1, $reflectionClass->getMethod('greet')->getAttributes(\Override::class));
        self::assertCount(1, $reflectionClass->getMethod('describe')->getAttributes(\Override::class));
    }

    /**
     * @test
     */
    public function newWithoutParenthesesWorksInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithNewWithoutParentheses::class);
        self::assertInstanceOf(ProxyInterface::class, $object);
        $object->setValue('a changed value');

        self::assertSame('described: the initial value', $object->describeNewSelf());
        self::assertSame('described: the initial value', $object->describeNewStatic());
        self::assertSame('described: the initial value', $object->describeNewClassName());
        self::assertSame('the initial value', $object->readValueOfNewSelf());

        self::assertSame('described: a changed value', $object->describe(), 'The original instance must not be affected');
    }

    /**
     * @test
     */
    public function newSelfInsideProxiedClassesCreatesFullyInitializedProxyInstances(): void
    {
        $object = $this->objectManager->get(ClassWithNewWithoutParentheses::class);
        $newInstance = $object->createNewSelf();

        self::assertInstanceOf(ClassWithNewWithoutParentheses::class, $newInstance);
        self::assertInstanceOf(ProxyInterface::class, $newInstance);
        self::assertInstanceOf(SingletonClassA::class, $newInstance->getSingletonA());
    }

    /**
     * @test
     */
    public function dynamicClassConstantFetchWorksInProxiedClasses(): void
    {
        $object = $this->objectManager->get(ClassWithDynamicConstantFetch::class);
        self::assertInstanceOf(ProxyInterface::class, $object);

        self::assertSame('the first value', $object->fetchViaSelf('FIRST'));
        self::assertSame('the second value', $object->fetchViaSelf('SECOND'));
        self::assertSame('the first value', $object->fetchViaStatic('FIRST'));
        self::assertSame('the second value', $object->fetchViaClassName('SECOND'));
    }
}
