<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\CLDR;

/* *
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
 * A model representing data from many CLDR files having hierarchical relation
 * between themselves.
 *
 * As for now, implementation is very simple. When a path provided is not
 * available in the most specific locale, same path is searched in parent
 * locale, and so on up to the root locale.
 *
 * The correctness of this soulution depends on path queries provided to get()
 * method. If they are as specific as possible (eg pointing a leaf node), then
 * there is no problem. But when path points whole branch of the XML tree, some
 * elements from parents locales should be merged according to inheritance,
 * but won't be as this sould probably invoke XSL transformations.
 *
 * Current usage cases of CLDR data by Locale subsystem are simple enough
 * so this implementation is sufficient.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class HierarchicalCLDRModel implements \F3\FLOW3\Locale\CLDR\CLDRModelInterface {

	/**
	 * A collection of models in hierarchy.
	 *
	 * @var Array of \F3\FLOW3\Locale\CLDR\CLDRModelInterface
	 */
	protected $models;

	/**
	 * Index of next model to be searched when getNext() will be invoked.
	 *
	 * @var int
	 */
	protected $nextModelIndex;

	/**
	 * Xpath query to use during the getNext() calls.
	 *
	 * @var string
	 */
	protected $searchQuery;

	/**
	 * Constructs the model.
	 *
	 * An array of CLDRModel instances is required. They have to be sorted
	 * with hierarchy in mind - the higher index in array, the more general
	 * file is (i.e. root should be on last index).
	 *
	 * @param array $models An array of \F3\FLOW3\CLDR\CLDRModel instances
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject(array $models) {
		$this->models = $models;
	}

	/**
	 * Returns multi-dimensional array representing desired node and it's children.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function get($path) {
		foreach ($this->models as $model) {
			$parsedNodes = $model->get($path);

			if ($parsedNodes !== FALSE) {
				return $parsedNodes;
			}
		}

		return FALSE;
	}

	/**
	 * Sets the query to use during iterative searching done by getNextResult()
	 * calls.
	 *
	 * @param string $query An Xpath query
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setQueryPath($query) {
		$this->searchQuery = $query;
		$this->nextModelIndex = count($this->models) - 1;
	}

	/**
	 * Returns an array with query results for path set by setQueryPath() method,
	 * from the next model in chain, starting with root model.
	 *
	 * It's an alternative for get() method, when there is a need to get merged
	 * result of the same query from all models.
	 *
	 * Note: Both get() and getNextResult() methods are workarounds for real
	 * hierarchy support, which would generate and return array of merged
	 * elements from all XML files in chain, taking into account whole tree
	 * branch, which isn't simple. This would probably be very costly, right?
	 *
	 * @return mixed Array of matching data, FALSE if no match for current model. NULL if iteration ended
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getNextResult() {
		if (empty($this->searchQuery)) {
			return NULL;
		}

		if ($this->nextModelIndex === -1) {
			return NULL;
		}

		return $this->models[$this->nextModelIndex--]->get($this->searchQuery);
	}
}

?>