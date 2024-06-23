<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Session\Data;

use Neos\Flow\Utility\Algorithms;

/**
 * The session identifier that in a string form is written in the session cookie
 * and identifies a SessionMetadata object from the SessionMetaDataStore that represents
 * a stored session without the key value store.
 *
 * @internal
 */
readonly class SessionIdentifier
{
    private function __construct(
        public string $value
    ) {
    }

    public static function createFromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @throws \Exception
     */
    public static function createRandom(): self
    {
        return new self(Algorithms::generateRandomString(32));
    }

    public function equals(SessionIdentifier $other): bool
    {
        return $this->value === $other->value;
    }
}
