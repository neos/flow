<?php
namespace Neos\Flow\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception;

/**
 * An abstract View
 *
 * @phpstan-consistent-constructor
 * @api
 */
abstract class AbstractView implements ViewInterface
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     * Syntax example:
     *     array(
     *         'someOptionName' => array('defaultValue', 'some description', 'string'),
     *         'someOtherOptionName' => array('defaultValue', some description', integer),
     *         ...
     *     )
     *
     * @var array
     */
    protected $supportedOptions = [];

    /**
     * The configuration options of this view
     * @see $supportedOptions
     *
     * @var array
     */
    protected $options = [];

    /**
     * View variables and their values
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * @var ControllerContext
     * @deprecated if you absolutely need access to the current request please assign a variable.
     *             when using the action controller the request is directly available at "request"
     */
    protected $controllerContext;

    /**
     * Factory method to create an instance with given options.
     *
     * @param array $options
     * @return static
     */
    public static function createWithOptions(array $options): self
    {
        return new static($options);
    }

    /**
     * Set default options based on the supportedOptions provided
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new Exception(sprintf('The view options "%s" you\'re trying to set don\'t exist in class "%s".', implode(',', array_keys($unsupportedOptions)), get_class($this)), 1359625876);
        }

        // check for required options being set
        array_walk(
            $this->supportedOptions,
            function ($supportedOptionData, $supportedOptionName, $options) {
                if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
                    throw new Exception('Required view option not set: ' . $supportedOptionName, 1359625876);
                }
            },
            $options
        );

        // merge with default values
        $this->options = array_merge(
            array_map(
                function ($value) {
                    return $value[0];
                },
                $this->supportedOptions
            ),
            $options
        );
    }

    /**
     * Get a specific option of this View
     *
     * @param string $optionName
     * @return mixed
     * @throws Exception
     */
    public function getOption($optionName)
    {
        if (!array_key_exists($optionName, $this->supportedOptions)) {
            throw new Exception(sprintf('The view option "%s" you\'re trying to get doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
        }

        return $this->options[$optionName];
    }

    /**
     * Set a specific option of this View
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function setOption($optionName, $value)
    {
        if (!array_key_exists($optionName, $this->supportedOptions)) {
            throw new Exception(sprintf('The view option "%s" you\'re trying to set doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
        }

        $this->options[$optionName] = $value;
    }

    /**
     * Add a variable to $this->variables.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return $this for chaining
     * @api
     */
    public function assign(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->variables.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2)
     * @return $this for chaining
     * @api
     */
    public function assignMultiple(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    /**
     * Sets the current controller context
     *
     * @deprecated if you absolutely need access to the current request please assign a variable.
     *             when using the action controller the request is directly available at "request"
     * @param ControllerContext $controllerContext Context of the controller associated with this view
     * @return void
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }
}
