<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Laminas\Code\Reflection\MethodReflection;
use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithVariousMethods;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for the Proxy Method Generator
 */
class ProxyMethodGeneratorTest extends UnitTestCase
{
    /**
     * Creates a proxy method generator for one of the methods of the ClassWithVariousMethods fixture
     */
    private function createProxyMethodFor(string $methodName): ProxyMethodGenerator
    {
        return ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection(ClassWithVariousMethods::class, $methodName));
    }

    /**
     * @test
     */
    public function renderBodyCodeReturnsAnEmptyStringIfNoCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        self::assertSame('', $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function renderBodyCodeReturnsAnEmptyStringIfOnlyWhitespaceWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('   ');
        $proxyMethod->addPostParentCallCode("\t");
        self::assertSame('', $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function willBeRenderedReturnsFalseIfNoCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        self::assertFalse($proxyMethod->willBeRendered());
    }

    /**
     * @test
     */
    public function willBeRenderedReturnsTrueIfPreParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        self::assertTrue($proxyMethod->willBeRendered());
    }

    /**
     * @test
     */
    public function willBeRenderedReturnsTrueIfPostParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPostParentCallCode('$foo = 1;');
        self::assertTrue($proxyMethod->willBeRendered());
    }

    /**
     * @test
     */
    public function generateReturnsAnEmptyStringIfNoCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        self::assertSame('', $proxyMethod->generate());
    }

    /**
     * @test
     */
    public function preParentCallCodeIsRenderedBeforeTheParentCall(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$foo = 1;', $bodyCode);
        self::assertStringContainsString('return parent::methodWithParameters($first, $second, $third, $fourth, $fifth, $sixth);', $bodyCode);
        self::assertLessThan(strpos($bodyCode, 'parent::'), strpos($bodyCode, '$foo = 1;'));
    }

    /**
     * @test
     */
    public function multiplePiecesOfAddedCodeAreRenderedInTheOrderTheyWereAdded(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('$first = 1;');
        $proxyMethod->addPreParentCallCode('$second = 2;');
        $proxyMethod->addPostParentCallCode('$third = 3;');
        $proxyMethod->addPostParentCallCode('$fourth = 4;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertLessThan(strpos($bodyCode, '$second = 2;'), strpos($bodyCode, '$first = 1;'));
        self::assertLessThan(strpos($bodyCode, '$third = 3;'), strpos($bodyCode, '$second = 2;'));
        self::assertLessThan(strpos($bodyCode, '$fourth = 4;'), strpos($bodyCode, '$third = 3;'));
    }

    /**
     * @test
     */
    public function postParentCallCodeLeadsToTheParentCallResultBeingAssignedAndReturned(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        $proxyMethod->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$result = parent::methodWithParameters($first, $second, $third, $fourth, $fifth, $sixth);', $bodyCode);
        self::assertStringContainsString('return $result;', $bodyCode);
        self::assertLessThan(strpos($bodyCode, '$result = parent::'), strpos($bodyCode, '$foo = 1;'));
        self::assertLessThan(strpos($bodyCode, 'return $result;'), strpos($bodyCode, '$bar = 2;'));
    }

    /**
     * @test
     */
    public function methodsWithoutAReturnTypeAreTreatedLikeMethodsReturningAValue(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithoutReturnType');
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        $proxyMethod->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$result = parent::methodWithoutReturnType($argument);', $bodyCode);
        self::assertStringContainsString('return $result;', $bodyCode);
    }

    public static function voidAndNeverReturnTypesDataProvider(): array
    {
        return [
            'void' => ['methodName' => 'voidMethod'],
            'never' => ['methodName' => 'neverMethod'],
        ];
    }

    /**
     * @test
     * @dataProvider voidAndNeverReturnTypesDataProvider
     */
    public function neitherReturnStatementNorResultAssignmentIsRenderedForVoidAndNeverReturnTypes(string $methodName): void
    {
        $proxyMethod = $this->createProxyMethodFor($methodName);
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        $proxyMethod->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('parent::' . $methodName . '($argument);', $bodyCode);
        self::assertStringNotContainsString('$result', $bodyCode);
        self::assertStringNotContainsString('return', $bodyCode);
    }

    /**
     * Note: This documents the current behavior. For methods returning void or never, the
     *       call to the parent method is skipped entirely as long as no post parent call
     *       code was added – which arguably is a bug, because the original method is then
     *       never executed.
     *
     * @test
     * @dataProvider voidAndNeverReturnTypesDataProvider
     */
    public function noParentCallIsRenderedForVoidAndNeverReturnTypesIfOnlyPreParentCallCodeExists(string $methodName): void
    {
        $proxyMethod = $this->createProxyMethodFor($methodName);
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertSame("\$foo = 1;\n", $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function noParentCallIsRenderedIfTheOriginalClassIsUnknown(): void
    {
        $proxyMethod = new ProxyMethodGenerator('someMethod');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertSame("\$foo = 1;\n", $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function nullIsAssignedAsResultIfTheOriginalClassIsUnknownButPostParentCallCodeExists(): void
    {
        $proxyMethod = new ProxyMethodGenerator('someMethod');
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        $proxyMethod->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$result = null;', $bodyCode);
        self::assertStringContainsString('return $result;', $bodyCode);
        self::assertStringNotContainsString('parent::', $bodyCode);
    }

    /**
     * @test
     */
    public function noParentCallIsRenderedIfTheOriginalClassDoesNotHaveTheMethod(): void
    {
        $proxyMethod = new ProxyMethodGenerator('nonExistingMethod');
        $proxyMethod->setFullOriginalClassName(ClassWithVariousMethods::class);
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertStringNotContainsString('parent::', $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockCopiesParameterNamesAndTypes(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $parameters = $proxyMethod->getParameters();

        self::assertSame(['first', 'second', 'third', 'fourth', 'fifth', 'sixth'], array_keys($parameters));
        self::assertSame('string', $parameters['first']->getType());
        self::assertSame('array', $parameters['second']->getType());
        self::assertSame('ArrayObject', $parameters['third']->getType());
        self::assertNull($parameters['fourth']->getType());
        self::assertSame('int', $parameters['fifth']->getType());
        self::assertSame('42', (string)$parameters['fifth']->getDefaultValue());
        self::assertSame('string', (string)$proxyMethod->getReturnType());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockCopiesTheByReferenceFlagOfParameters(): void
    {
        $parameters = $this->createProxyMethodFor('methodWithParameters')->getParameters();

        self::assertFalse($parameters['first']->getPassedByReference());
        self::assertTrue($parameters['fourth']->getPassedByReference());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockCopiesTheStaticFlag(): void
    {
        self::assertTrue($this->createProxyMethodFor('staticMethod')->isStatic());
        self::assertFalse($this->createProxyMethodFor('methodWithParameters')->isStatic());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockCopiesTheVisibility(): void
    {
        self::assertSame(ProxyMethodGenerator::VISIBILITY_PUBLIC, $this->createProxyMethodFor('methodWithParameters')->getVisibility());
        self::assertSame(ProxyMethodGenerator::VISIBILITY_PROTECTED, $this->createProxyMethodFor('protectedMethod')->getVisibility());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockCopiesTheDocBlockIfThereIsOne(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        self::assertNotNull($proxyMethod->getDocBlock());
        self::assertSame('Some documentation for this method', $proxyMethod->getDocBlock()->getShortDescription());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockLeavesTheDocBlockEmptyIfTheOriginalMethodHasNone(): void
    {
        self::assertNull($this->createProxyMethodFor('staticMethod')->getDocBlock());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockRemembersTheOriginalClassName(): void
    {
        self::assertSame(ClassWithVariousMethods::class, $this->createProxyMethodFor('methodWithParameters')->getFullOriginalClassName());
    }

    /**
     * @test
     */
    public function copyMethodSignatureAndDocblockRendersAttributesOfTheOriginalMethod(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithAttribute');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertStringContainsString(
            "#[\\Neos\\Flow\\Tests\\Unit\\ObjectManagement\\Fixture\\ExampleMethodAttribute('some label')]",
            $proxyMethod->generate()
        );
    }

    /**
     * @test
     */
    public function fromReflectionCopiesTheMethodSignature(): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariousMethods::class, 'methodWithParameters'));

        self::assertSame(['first', 'second', 'third', 'fourth', 'fifth', 'sixth'], array_keys($proxyMethod->getParameters()));
        self::assertSame('string', (string)$proxyMethod->getReturnType());
        self::assertTrue($proxyMethod->getParameters()['fourth']->getPassedByReference());
    }

    /**
     * @test
     */
    public function fromReflectionCopiesTheStaticFlagAndTheOriginalClassName(): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariousMethods::class, 'staticMethod'));

        self::assertTrue($proxyMethod->isStatic());
        self::assertSame(ClassWithVariousMethods::class, $proxyMethod->getFullOriginalClassName());
    }

    /**
     * Note: This documents the current behavior. In contrast to copyMethodSignatureAndDocblock(),
     *       fromReflection() also copies the body of the original method, which means that the
     *       resulting proxy method is rendered even if no code was added. This method is currently
     *       not used by any of the proxy class builders.
     *
     * @test
     */
    public function fromReflectionAlsoCopiesTheOriginalMethodBody(): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariousMethods::class, 'staticMethod'));

        self::assertStringContainsString('return $number;', $proxyMethod->getBody());
        self::assertTrue($proxyMethod->willBeRendered());
    }

    /**
     * @test
     */
    public function fromReflectionRendersAttributesOfTheOriginalMethod(): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariousMethods::class, 'methodWithAttribute'));
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertStringContainsString(
            "#[\\Neos\\Flow\\Tests\\Unit\\ObjectManagement\\Fixture\\ExampleMethodAttribute('some label')]",
            $proxyMethod->generate()
        );
    }

    /**
     * @test
     */
    public function setFullOriginalClassNameOverridesTheOriginalClassName(): void
    {
        $proxyMethod = new ProxyMethodGenerator('someMethod');
        self::assertNull($proxyMethod->getFullOriginalClassName());

        $proxyMethod->setFullOriginalClassName(ClassWithVariousMethods::class);
        self::assertSame(ClassWithVariousMethods::class, $proxyMethod->getFullOriginalClassName());
    }

    /**
     * @test
     */
    public function buildMethodParametersCodeRendersTheParameterNamesOnlyByDefault(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');

        self::assertSame(
            '$first, $second, $third, $fourth, $fifth, $sixth',
            $proxyMethod->buildMethodParametersCode(ClassWithVariousMethods::class, 'methodWithParameters', false)
        );
    }

    /**
     * @test
     */
    public function buildMethodParametersCodeRendersTypesAndDefaultValuesIfRequested(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');

        self::assertSame(
            'string $first, array $second, \ArrayObject $third, &$fourth, int $fifth = 42, bool $sixth = true',
            $proxyMethod->buildMethodParametersCode(ClassWithVariousMethods::class, 'methodWithParameters', true)
        );
    }

    /**
     * @test
     */
    public function buildMethodParametersCodeReturnsAnEmptyStringIfTheClassOrMethodIsUnknown(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');

        self::assertSame('', $proxyMethod->buildMethodParametersCode(null, 'methodWithParameters', false));
        self::assertSame('', $proxyMethod->buildMethodParametersCode(ClassWithVariousMethods::class, null, false));
        self::assertSame('', $proxyMethod->buildMethodParametersCode(ClassWithVariousMethods::class, 'nonExistingMethod', false));
    }

    /**
     * @test
     */
    public function generateRendersTheCompleteMethodIncludingSignatureAndBody(): void
    {
        $proxyMethod = $this->createProxyMethodFor('methodWithParameters');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        $code = $proxyMethod->generate();

        self::assertStringContainsString('public function methodWithParameters(string $first, array $second, \ArrayObject $third, &$fourth, int $fifth = 42, bool $sixth = true)', $code);
        self::assertStringContainsString(': string', $code);
        self::assertStringContainsString('$foo = 1;', $code);
        self::assertStringContainsString('return parent::methodWithParameters(', $code);
    }
}
