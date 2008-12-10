<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\RequestHandlerResolver.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Analyzes the raw request and delivers a request handler which can handle it.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\RequestHandlerResolver.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestHandlerResolver {

	/**
	 * @var \F3\FLOW3\ObjectManagerInterface Reference to the object manager
	 */
	protected $objectManager;

	/**
	 * The FLOW3 settings
	 * @var array
	 */
	protected $configuration;

	/**
	 * Constructs the Request Handler Resolver
	 *
	 * @param array $settings The FLOW3 settings
	 * @param \F3\FLOW3\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(array $settings, \F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->settings = $settings;
		$this->objectManager = $objectManager;
	}

	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return \F3\FLOW3\MVC\RequestHandler A request handler
	 * @throws \F3\FLOW3\MVC\Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveRequestHandler() {
		$availableRequestHandlerClassNames = $this->settings['mvc']['availableRequestHandlers'];

		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			$requestHandler = $this->objectManager->getObject($requestHandlerClassName);
			if ($requestHandler->canHandleRequest()) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) throw new LogicException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (count($suitableRequestHandlers) == 0) throw new \F3\FLOW3\MVC\Exception('No suitable request handler found.', 1205414233);
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}
}

?>