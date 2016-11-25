<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

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
 * A target class for testing introductions
 *
 */
class TargetClass03
{
    public $originalMethodCalled;

    /**
     * @return void
     */
    public function originalMethod()
    {
        $this->originalMethodCalled = true;
    }
}
