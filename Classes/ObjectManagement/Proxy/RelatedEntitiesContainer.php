<?php
namespace Neos\Flow\ObjectManagement\Proxy;

use Doctrine\Persistence\Proxy as DoctrineProxy;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;

/**
 * This is a mutable container to hold references to entities (class & identifier) for serialization.
 * Userland code should never (have to) interact with this, it is used in proxy classes only. You might
 * see references to it in serialized object strings.
 * @internal
 */
#[Flow\Proxy(false)]
final class RelatedEntitiesContainer implements \IteratorAggregate
{
    protected array $relatedEntities = [];

    public function getIterator(): \Generator
    {
        foreach ($this->relatedEntities as $entityInformation) {
            yield $entityInformation;
        }
    }

    public function reset(): void
    {
        $this->relatedEntities = [];
    }

    public function appendRelatedEntity(string $originalPropertyName, string $path, object $propertyValue): void
    {
        if ($propertyValue instanceof DoctrineProxy) {
            $className = get_parent_class($propertyValue);
        } else {
            $className = Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
        }
        $identifier = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($propertyValue);
        if (!$identifier && $propertyValue instanceof DoctrineProxy) {
            $identifier = current(ObjectAccess::getProperty($propertyValue, '_identifier', true));
        }

        $this->relatedEntities[$originalPropertyName . '.' . $path] = [
            'propertyName' => $originalPropertyName,
            'entityType' => $className,
            'identifier' => $identifier,
            'entityPath' => $path
        ];
    }
}
