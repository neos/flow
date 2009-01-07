<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Token;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for username/password authentication token
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class UsernamePasswordTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function credentialsAreSetCorrectlyFromPOSTArguments() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$POSTArguments = array(
			'F3\FLOW3\Security\Authentication\Token\UsernamePassword::username' => 'FLOW3',
			'F3\FLOW3\Security\Authentication\Token\UsernamePassword::password' => 'verysecurepassword'
		);
		
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getPOSTArguments')->will($this->returnValue($POSTArguments));

		$token = new \F3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token->injectObjectFactory($mockObjectFactory);		
		$token->injectEnvironment($mockEnvironment);
		$token->updateCredentials();

		$expectedCredentials = array ('username' => 'FLOW3', 'password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationEntryPointReturnsTheConfiguredAuthenticationEntryPoint() {
		$this->markTestIncomplete();
	}
}
?>