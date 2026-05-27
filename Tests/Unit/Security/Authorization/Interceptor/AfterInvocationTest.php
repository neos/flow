<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security;

/**
 * Testcase for the policy enforcement interceptor
 */
final class AfterInvocationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted()
    {
        $mockSecurityContext = $this->createStub(Security\Context::class);
        $mockAfterInvocationManager = $this->createStub(Security\Authorization\AfterInvocationManagerInterface::class);

        $theResult = new \ArrayObject(['some' => 'stuff']);

        $interceptor = new Security\Authorization\Interceptor\AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
        $interceptor->setResult($theResult);
        self::assertSame($theResult, $interceptor->invoke());
    }
}
