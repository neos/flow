<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * An interface used to introduce certain methods to support object persistence
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface DirtyMonitoringInterface {

	/**
	 * If the monitored object has ever been persisted
	 *
	 * @return boolean TRUE if the object is new, otherwise FALSE
	 */
	public function isNew();

	/**
	 * If the specified property of the reconstituted object has been modified
	 * since it woke up.
	 *
	 * @param string $propertyName Name of the property to check
	 * @return boolean TRUE if the given property has been modified
	 */
	public function isDirty($propertyName);

	/**
	 * Resets the dirty flags of all properties to signal that the object is
	 * clean again after being persisted.
	 *
	 * The $joinPoint argument here is a special case, as the introduced
	 * method is used from within an advice and "externally", thus we need
	 * to handle this specially
	 *
	 * @param JoinPointInterface $joinPoint Current joinpoint, if used from within an advice
	 * @return void
	 */
	public function memorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint = NULL);
}
?>