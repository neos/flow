<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator for alphanumeric strings
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Alphanumeric extends \F3\FLOW3\Validation\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid
	 * alphanumeric string, which is defined as [a-zA-Z0-9]*.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $propertyValue The value that should be validated
	 * @param \F3\FLOW3\Validation\Errors $errors Any occured Error will be stored here
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws \F3\FLOW3\Validation\Exception\InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isValidProperty($propertyValue, \F3\FLOW3\Validation\Errors &$errors) {

		if (is_string($propertyValue) && preg_match('/^[a-z0-9]*$/i', $propertyValue)) return TRUE;

		$errors->append($this->objectFactory->create('F3\FLOW3\Validation\Error', 'The given subject was not a valid integer. Got: "' . $propertyValue . '"', 1221551320));
		return FALSE;
	}
}

?>