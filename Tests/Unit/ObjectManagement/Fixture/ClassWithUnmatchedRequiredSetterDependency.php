<?php
namespace Neos\Flow\Tests\Object\Fixture;

class ClassWithUnmatchedRequiredSetterDependency
{
    public $requiredSetterArgument;

    public function injectRequiredSetterArgument(\stdClass $setterArgument)
    {
        $this->requiredSetterArgument = $setterArgument;
    }
}
