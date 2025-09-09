<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Neos\Flow\ObjectManagement\Exception\ProxyCompilerException;

/**
 * Used to disable proxy building for an object.
 *
 * If disabled, neither Dependency Injection nor AOP can be used
 * on the object.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Proxy
{
    /**
     * Whether proxy building for the target is disabled. (Can be given as anonymous argument.)
     * @var boolean
     */
    public $enabled = true;

    /**
     * Whether you need serialization code build in the proxy, this might be needed if you
     * build a PHP object you need to serialize that includes entities for example, as in that
     * case the entities should be converted to metadata (class & persistence identifier) before
     * serialization.
     * The serialization code also removes injected/otherwise internal framework properties
     * introduced by the proxy building but these situations should be correctly detected by
     * proxy building and create the serialization code anyway so you should never have to
     * set this for these cases and it would be a bug if you have to.
     *
     * At this point it wouldn't make much sense to allow a forced disabling of the serialization
     * code as that would most certainly run into problems if there was AOP, injections or other reasons.
     * Rather disable the proxy completely then.
     *
     * @var bool
     */
    public $forceSerializationCode = false;

    public function __construct(bool $enabled = true, bool $forceSerializationCode = false)
    {
        if ($enabled === false && $forceSerializationCode === true) {
            throw new ProxyCompilerException('Cannot disable a Proxy but forceSerializationCode at the same time.', 1756813222);
        }

        $this->enabled = $enabled;
        $this->forceSerializationCode = $forceSerializationCode;
    }
}
