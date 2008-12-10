<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC;

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
 * @version $Id$
 */

require_once(__DIR__ . '/Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController.php');
require_once(__DIR__ . '/Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockExceptionThrowingController.php');

/**
 * Testcase for the MVC Dispatcher
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::Object::TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DispatcherTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::MVC::Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$securityContextHolder = $this->getMock('F3::FLOW3::Security::ContextHolderInterface');
		$firewall = $this->getMock('F3::FLOW3::Security::Authorization::FirewallInterface');
		$settings = array();
		$configurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array('getSettings'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));

		$this->dispatcher = new F3::FLOW3::MVC::Dispatcher($this->objectManager, $this->objectFactory);
		$this->dispatcher->injectSecurityContextHolder($securityContextHolder);
		$this->dispatcher->injectFirewall($firewall);
		$this->dispatcher->injectConfigurationManager($configurationManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aStopActionExceptionThrownByTheControllerIsCatchedByTheDispatcherAndBreaksTheDispatchLoop() {
		$request = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$request->injectObjectManager($this->objectManager);
		$response = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Response');

		$mockPropertyMapper = $this->getMock('F3::FLOW3::Property::Mapper', array(), array(), '', FALSE);
		$mockPropertyMapper->expects($this->any())->method('getMappingResults')->will($this->returnValue(new F3::FLOW3::Property::MappingResults));

		$this->objectManager->registerObject('F3::FLOW3::MVC::Fixture::Controller::MockExceptionThrowingController');
		$mockExceptionThrowingController = $this->objectManager->getObject('F3::FLOW3::MVC::Fixture::Controller::MockExceptionThrowingController');
		$mockExceptionThrowingController->injectPropertyMapper($mockPropertyMapper);

		$request->setControllerPackageKey('FLOW3');
		$request->setControllerObjectNamePattern('F3::@package::MVC::Fixture::Controller::@controller');
		$request->setControllerName('MockExceptionThrowingController');

		$request->setControllerActionName('stopAction');
		$this->dispatcher->dispatch($request, $response);

		$request->setDispatched(FALSE);
		$request->setControllerActionName('throwGeneralException');
		try {
			$this->dispatcher->dispatch($request, $response);
			$this->fail('The exception thrown by the second action was catched somewhere or the action was not called.');
		} catch (F3::FLOW3::MVC::Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatcherCallsProcessRequestMethodOfController() {
		$request = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$request->injectObjectManager($this->objectManager);
		$response = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Response');

		$mockPropertyMapper = $this->getMock('F3::FLOW3::Property::Mapper', array(), array(), '', FALSE);
		$mockPropertyMapper->expects($this->any())->method('getMappingResults')->will($this->returnValue(new F3::FLOW3::Property::MappingResults));

		$this->objectManager->registerObject('F3::FLOW3::MVC::Fixture::Controller::MockRequestHandlingController');
		$controller = $this->objectManager->getObject('F3::FLOW3::MVC::Fixture::Controller::MockRequestHandlingController');
		$controller->injectPropertyMapper($mockPropertyMapper);

		$request->setControllerPackageKey('FLOW3');
		$request->setControllerObjectNamePattern('F3::@package::MVC::Fixture::Controller::@controller');
		$request->setControllerName('MockRequestHandlingController');

		$this->dispatcher->dispatch($request, $response);
		$this->assertTrue($controller->requestHasBeenProcessed, 'It seems like the controller has not been called by the dispatcher.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDispatcherInjectsThePackageSettingsIntoTheController() {
		$settings = array();
		$configurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array('getSettings'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));

		$this->dispatcher->injectConfigurationManager($configurationManager);

		$request = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$request->injectObjectManager($this->objectManager);
		$response = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Response');

		$mockPropertyMapper = $this->getMock('F3::FLOW3::Property::Mapper', array(), array(), '', FALSE);
		$mockPropertyMapper->expects($this->any())->method('getMappingResults')->will($this->returnValue(new F3::FLOW3::Property::MappingResults));

		$this->objectManager->registerObject('F3::FLOW3::MVC::Fixture::Controller::MockRequestHandlingController');
		$controller = $this->objectManager->getObject('F3::FLOW3::MVC::Fixture::Controller::MockRequestHandlingController');
		$controller->injectPropertyMapper($mockPropertyMapper);

		$request->setControllerPackageKey('FLOW3');
		$request->setControllerObjectNamePattern('F3::@package::MVC::Fixture::Controller::@controller');
		$request->setControllerName('MockRequestHandlingController');

		$this->dispatcher->dispatch($request, $response);
		$this->assertSame($settings, $controller->getSettings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theDispatcherInitializesTheSecurityContextWithTheGivenRequest() {
		$request = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerObjectNamePattern('F3::@package::MVC::Controller::@controllerController');
		$response = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Response');

		$securityContextHolder = $this->getMock('F3::FLOW3::Security::ContextHolderInterface', array('initializeContext', 'setContext', 'getContext', 'clearContext'));
		$this->dispatcher->injectSecurityContextHolder($securityContextHolder);

		$securityContextHolder->expects($this->any())->method('initializeContext');
		$this->dispatcher->dispatch($request, $response);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theDispatcherCallsTheFirewallWithTheGivenRequest() {
		$request = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerObjectNamePattern('F3::@package::MVC::Controller::@controllerController');
		$response = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Response');

		$firewall = $this->getMock('F3::FLOW3::Security::Authorization::FirewallInterface');
		$this->dispatcher->injectFirewall($firewall);

		$firewall->expects($this->any())->method('blockIllegalRequests');
		$this->dispatcher->dispatch($request, $response);
	}
}
?>