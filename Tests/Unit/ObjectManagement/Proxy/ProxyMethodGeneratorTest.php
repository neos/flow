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

use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithVoidAndNeverMethods;
use Neos\Flow\Tests\UnitTestCase;

class ProxyMethodGeneratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderBodyCodeRendersParentCallWithReturnStatementIfOnlyPreParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodGenerator('doSomething', 'string');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        $bodyCode = $proxyMethod->renderBodyCode();
        self::assertStringContainsString("\$foo = 1;\n", $bodyCode);
        self::assertStringContainsString('return parent::doSomething();', $bodyCode);
    }

    /**
     * @test
     */
    public function renderBodyCodeRendersParentCallWithoutReturnStatementForVoidMethodsIfOnlyPreParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodGenerator('doSomethingWithoutReturningAnything', 'void');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertSame("\$foo = 1;\n    parent::doSomethingWithoutReturningAnything();\n", $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function renderBodyCodeRendersParentCallWithoutReturnStatementForNeverMethodsIfOnlyPreParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodGenerator('failForSure', 'never');
        $proxyMethod->addPreParentCallCode('$foo = 1;');

        self::assertSame("\$foo = 1;\n    parent::failForSure();\n", $proxyMethod->renderBodyCode());
    }

    /**
     * @test
     */
    public function renderBodyCodeRendersParentCallWithoutAssignmentForVoidMethodsIfPreAndPostParentCallCodeWasAdded(): void
    {
        $proxyMethod = $this->createProxyMethodGenerator('doSomethingWithoutReturningAnything', 'void');
        $proxyMethod->addPreParentCallCode('$foo = 1;');
        $proxyMethod->addPostParentCallCode('$bar = 2;');

        self::assertSame("\$foo = 1;\n    parent::doSomethingWithoutReturningAnything();\n\$bar = 2;\n", $proxyMethod->renderBodyCode());
    }

    private function createProxyMethodGenerator(string $methodName, string $returnType): ProxyMethodGenerator
    {
        $proxyMethod = new ProxyMethodGenerator($methodName);
        $proxyMethod->setReturnType($returnType);
        $proxyMethod->setFullOriginalClassName(ClassWithVoidAndNeverMethods::class);
        return $proxyMethod;
    }
}
