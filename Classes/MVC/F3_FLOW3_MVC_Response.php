<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\Response.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A generic and very basic response implementation
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\Response.php 467 2008-02-06 19:34:56Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class Response {

	/**
	 * @var string The response content
	 */
	protected $content = NULL;

	/**
	 * Overrides and sets the content of the response
	 *
	 * @param string $content The response content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendContent($content) {
		$this->content .= $content;
	}

	/**
	 * Returns the response content without sending it.
	 *
	 * @return string The response content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Sends the response
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function send() {
		if ($this->content !== NULL) {
			echo $this->getContent();
		}
	}
}
?>