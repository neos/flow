<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Reader;
use Neos\Flow\Reflection\ReflectionService;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class FlowAnotationReader implements Reader
{
    private ReflectionService $reflectionService;

    public function __construct(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Gets the annotations applied to a class.
     *
     * @param ReflectionClass $class The ReflectionClass of the class from which the class annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getClassAnnotations(ReflectionClass $class)
    {
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getClassAnnotations($class->getName()) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a class annotation.
     *
     * @param ReflectionClass $class The ReflectionClass of the class from which the class annotations should be read.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->reflectionService->getClassAnnotation($class->getName(), $annotationName);
    }

    /**
     * Gets the annotations applied to a method.
     *
     * @param ReflectionMethod $method The ReflectionMethod of the method from which the annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getMethodAnnotations(ReflectionMethod $method)
    {
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getMethodAnnotations($method->class, $method->getName()) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a method annotation.
     *
     * @param ReflectionMethod $method The ReflectionMethod to read the annotations from.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->reflectionService->getMethodAnnotation($method->class, $method->getName(), $annotationName);
    }

    /**
     * Gets the annotations applied to a property.
     *
     * @param ReflectionProperty $property The ReflectionProperty of the property from which the annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getPropertyAnnotations($property->class, $property->getName()) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a property annotation.
     *
     * @param ReflectionProperty $property The ReflectionProperty to read the annotations from.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        return $this->reflectionService->getPropertyAnnotation($property->class, $property->getName(), $annotationName);
    }
}
