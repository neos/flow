<?php
namespace Neos\Flow\Security\Authentication\Token;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authentication\TokenInterface;

/**
 * Interface for authentication tokens which only hold a password
 */
interface PasswordTokenInterface extends TokenInterface
{
    /**
     * @return string The password this token represents
     */
    public function getPassword(): string;
}
