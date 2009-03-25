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
 * Adds the aspect of persistence to repositories
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class DirtyMonitoring {

	/**
	 * The persistence manager
	 *
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @introduce F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface, F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject
	 */
	public $dirtyMonitoringInterface;

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isNew() method introduced above
	 *
	 * @param \F3\FLOW3\AOPJoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around method(.*->FLOW3_Persistence_isNew())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return !property_exists($proxy, 'FLOW3_Persistence_cleanProperties');
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isDirty() method introduced above
	 *
	 * @param \F3\FLOW3\AOPJoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around method(.*->FLOW3_Persistence_isDirty())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		$isDirty = FALSE;

		if (property_exists($proxy, 'FLOW3_Persistence_cleanProperties')) {
			$cleanProperties = $proxy->FLOW3_Persistence_cleanProperties;
			$uuidPropertyName = $this->persistenceManager->getClassSchema($joinPoint->getClassName())->getUUIDPropertyName();
			if ($uuidPropertyName !== NULL && $proxy->FLOW3_AOP_Proxy_getProperty($uuidPropertyName) != $cleanProperties[$uuidPropertyName]) {
				throw new \F3\FLOW3\Persistence\Exception\TooDirty('My property "' . $uuidPropertyName . '" tagged as @uuid has been modified, that is simply too much.', 1222871239);
			}

			$propertyName = $joinPoint->getMethodArgument('propertyName');
			if ($cleanProperties[$propertyName] !== $proxy->FLOW3_AOP_Proxy_getProperty($propertyName)) {
				$isDirty = TRUE;
			}
		}

		return $isDirty;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the FLOW3 persistence layer
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @before method(.*->FLOW3_Persistence_memorizeCleanState())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$cleanProperties = array();
		$propertyNames = array_keys($this->persistenceManager->getClassSchema($joinPoint->getClassName())->getProperties());

		foreach ($propertyNames as $propertyName) {
			$cleanProperties[$propertyName] = $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
		}
		$proxy->FLOW3_Persistence_cleanProperties = $cleanProperties;
	}

	/**
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @afterreturning F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject && method(.*->__clone())
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObject(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		unset($joinPoint->getProxy()->FLOW3_Persistence_cleanProperties);
	}
}
?>
