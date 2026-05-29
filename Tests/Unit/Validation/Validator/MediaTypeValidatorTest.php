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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\ResourceManagement\ResourceMetaDataInterface;
use Neos\Flow\Validation\Validator\MediaTypeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the media type validator
 *
 */
final class MediaTypeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = MediaTypeValidator::class;

    public function setUp(): void
    {
        $this->validatorOptions([
            'allowedTypes' => ['image/*', 'application/csv'],
            'disallowedTypes' => ['video/*', 'application/pdf']
        ]);
    }

    protected function createResourceMetaDataInterfaceMock(string $mediaType): ResourceMetaDataInterface
    {
        $mock = $this->createMock(ResourceMetaDataInterface::class);
        $mock->expects($this->once())->method('getMediaType')->willReturn($mediaType);
        return $mock;
    }

    protected function createUploadedFileInterfaceMock(string $mediaType): UploadedFileInterface
    {
        $mock = $this->createMock(UploadedFileInterface::class);
        $mock->expects($this->once())->method('getClientMediaType')->willReturn($mediaType);
        return $mock;
    }

    public static function emptyItems(): \Iterator
    {
        yield [null];
        yield [''];
    }

    #[DataProvider('emptyItems')]
    #[Test]
    public function validateAcceptsEmptyValue($item): void
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }


    public function itemsWithAllowedMediaType(): \Iterator
    {
        yield [$this->createResourceMetaDataInterfaceMock('image/jpeg')];
        yield [$this->createResourceMetaDataInterfaceMock('application/csv')];
        yield [$this->createUploadedFileInterfaceMock('image/jpeg')];
        yield [$this->createUploadedFileInterfaceMock('application/csv')];
    }

    #[DataProvider('itemsWithAllowedMediaType')]
    #[Test]
    public function validateAcceptsItemsWithAllowedMediaType($item): void
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithUnhandledTypes(): \Iterator
    {
        yield [12];
        yield ['hello'];
        yield [(object) []];
        yield [new \DateTime()];
    }

    #[DataProvider('itemsWithUnhandledTypes')]
    #[Test]
    public function validateRejectsItemsWithUnhandledTypes($item): void
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public function itemsWithDisallowedMediaType(): \Iterator
    {
        yield [$this->createResourceMetaDataInterfaceMock('video/mp4')];
        yield [$this->createResourceMetaDataInterfaceMock('application/pdf')];
        yield [$this->createUploadedFileInterfaceMock('video/mp4')];
        yield [$this->createUploadedFileInterfaceMock('application/pdf')];
    }

    #[DataProvider('itemsWithDisallowedMediaType')]
    #[Test]
    public function validateRejectsItemsWithDisallowedMediaType($item): void
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public function itemsWithOtherMediaType(): \Iterator
    {
        yield [$this->createResourceMetaDataInterfaceMock('text/plain')];
        yield [$this->createUploadedFileInterfaceMock('text/plain')];
    }

    #[DataProvider('itemsWithOtherMediaType')]
    #[Test]
    public function validateRejectsItemsWithOtherMediaType($item): void
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
