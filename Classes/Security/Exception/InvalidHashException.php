<?php
namespace Neos\Flow\Security\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A "InvalidHash" Exception, thrown when a HMAC validation failed.
 *
 * @api
 */
class InvalidHashException extends \Neos\Flow\Security\Exception
{
    protected $statusCode = 400;
}
