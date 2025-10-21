<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

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
 * Class CommonObject
 * Representation of an object handled as "\Doctrine\DBAL\Types\Type::OBJECT"
 */
class CommonObject
{
    /**
     * @var string
     */
    protected $foo;

    /**
     * @param string $foo
     * @return $this
     */
    public function setFoo($foo = null): self
    {
        $this->foo = $foo;
        return $this;
    }

    /**
     * @return string
     */
    public function getFoo(): string
    {
        return $this->foo;
    }
}
