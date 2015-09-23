<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassC
{
    /**
     * @var string
     */
    public $settingsArgument;

    /**
     * @param string $settingsArgument
     */
    public function __construct($settingsArgument)
    {
        $this->settingsArgument = $settingsArgument;
    }

    /**
     * @return string
     */
    public function getSettingsArgument()
    {
        return $this->settingsArgument;
    }
}
