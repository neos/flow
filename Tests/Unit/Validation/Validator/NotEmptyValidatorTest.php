<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Validation\Validator\NotEmptyValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the not empty validator
 *
 */
final class NotEmptyValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = NotEmptyValidator::class;

    /**
     * @test
     */
    public function notEmptyValidatorReturnsNoErrorForASimpleString()
    {
        self::assertFalse($this->validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString()
    {
        self::assertTrue($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue()
    {
        self::assertTrue($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyArray()
    {
        self::assertTrue($this->validator->validate([])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyCountableObject()
    {
        self::assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject()
    {
        self::assertCount(1, $this->validator->validate('')->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue()
    {
        self::assertCount(1, $this->validator->validate(null)->getErrors());
    }
}
