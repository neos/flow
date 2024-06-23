<?php

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

/**
 * Supplier for lazily fetching the routes for the router.
 *
 * This layer of abstraction avoids having to parse the routes for every request.
 * The router will only request the routes if it comes across a route it hasn't seen (i.e. cached) before.
 *
 * @internal
 */
interface RoutesProviderInterface
{
    public function getRoutes(): Routes;
}
