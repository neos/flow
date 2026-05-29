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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Interceptor\RequireAuthentication;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the authentication required security interceptor
 */
final class RequireAuthenticationTest extends UnitTestCase
{
    #[Test]
    public function invokeCallsTheAuthenticationManagerToPerformAuthentication()
    {
        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new RequireAuthentication($authenticationManager);
        $interceptor->invoke();
    }
}
