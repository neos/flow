<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/*
class foo
*/
/*
class bar */class /* oddly placed comment for class */ ClassWithKeywordsInClassBody //class quux
{
    public function doSomething()
    {
        echo '
            only whitespace and lowercase class baz
        ';
    }
}
