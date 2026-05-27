<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;
use Neos\Flow\Utility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the URI Helper
 */
final class UriBuilderTest extends UnitTestCase
{
    /**
     * @var Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var Mvc\Routing\RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRouter;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockBaseUri;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockMainRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSubRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSubSubRequest;

    /**
     * Sets up the test case
     *
     */
    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);

        $this->mockBaseUri = $this->createMock(UriInterface::class);
        $mockBaseUriProvider = $this->createMock(Http\BaseUriProvider::class);
        $mockBaseUriProvider->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn($this->mockBaseUri);

        $this->mockRouter = $this->createMock(Mvc\Routing\RouterInterface::class);

        $this->mockMainRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockMainRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockMainRequest->method('getParentRequest')->willReturn(null);
        $this->mockMainRequest->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockMainRequest->method('isMainRequest')->willReturn(true);
        $this->mockMainRequest->method('getArgumentNamespace')->willReturn('');

        $this->mockSubRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockSubRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockSubRequest->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockSubRequest->method('isMainRequest')->willReturn(false);
        $this->mockSubRequest->method('getParentRequest')->willReturn($this->mockMainRequest);
        $this->mockSubRequest->method('getArgumentNamespace')->willReturn('SubNamespace');

        $this->mockSubSubRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockSubSubRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockSubSubRequest->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockSubSubRequest->method('isMainRequest')->willReturn(false);
        $this->mockSubSubRequest->method('getParentRequest')->willReturn($this->mockSubRequest);

        $environment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->onlyMethods(['isRewriteEnabled'])->getMock();
        $environment->method('isRewriteEnabled')->willReturn((true));

        $this->uriBuilder = new Mvc\Routing\UriBuilder();
        $this->inject($this->uriBuilder, 'router', $this->mockRouter);
        $this->inject($this->uriBuilder, 'environment', $environment);
        $this->inject($this->uriBuilder, 'baseUriProvider', $mockBaseUriProvider);
        $this->uriBuilder->setRequest($this->mockMainRequest);
    }

    /**
     * @test
     */
    public function settersAndGettersWorkAsExpected()
    {
        $this->uriBuilder
            ->reset()
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('TestFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments']);

        self::assertEquals(['test' => 'arguments'], $this->uriBuilder->getArguments());
        self::assertEquals('testSection', $this->uriBuilder->getSection());
        self::assertEquals('testformat', $this->uriBuilder->getFormat());
        self::assertEquals(true, $this->uriBuilder->getCreateAbsoluteUri());
        self::assertEquals(true, $this->uriBuilder->getAddQueryString());
        self::assertEquals(['test' => 'addQueryStringExcludeArguments'], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments()
    {
        $arguments = ['foo' => 'bar', 'additionalParam' => 'additionalValue'];
        $controllerArguments = ['foo' => 'overruled', 'baz' => ['Neos.Flow' => 'fluid']];
        $expectedArguments = ['foo' => 'overruled', 'additionalParam' => 'additionalValue', 'baz' => ['Neos.Flow' => 'fluid'], '@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage'];

        $this->uriBuilder->setArguments($arguments);
        $this->uriBuilder->uriFor('index', $controllerArguments, 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForThrowsExceptionIfActionNameIsNotSpecified()
    {
        $this->expectException(Mvc\Routing\Exception\MissingActionNameException::class);
        $this->uriBuilder->uriFor('', [], 'SomeController', 'SomePackage');
    }

    /**
     * @test
     */
    public function uriForSetsControllerFromRequestIfControllerIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerName')->willReturn(('SomeControllerFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], null, 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->willReturn(('SomePackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', null);
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->willReturn(('SomePackage'));
        $this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->willReturn(('SomeSubpackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithExplicitEmptySubpackageKeyDoesNotUseRequestSubpackageKey()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $mockSubRequest */
        $mockSubRequest = $this->createMock(Mvc\ActionRequest::class);
        $mockSubRequest->method('getHttpRequest')->willReturn(($this->mockHttpRequest));
        $mockSubRequest->method('getMainRequest')->willReturn(($this->mockMainRequest));
        $mockSubRequest->method('isMainRequest')->willReturn((false));
        $mockSubRequest->method('getParentRequest')->willReturn(($this->mockMainRequest));
        $mockSubRequest->method('getArgumentNamespace')->willReturn((''));
        $mockSubRequest->method('getControllerSubpackageKey')->willReturn(('SomeSubpackageKeyFromRequest'));

        $this->uriBuilder->setRequest($mockSubRequest);

        $expectedArguments = ['@action' => 'show', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => ''];

        $this->uriBuilder->uriFor('show', [], 'SomeController', 'SomePackage', '');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsFormatArgumentIfSpecified()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat'];

        $this->uriBuilder->setFormat('SomeFormat');
        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsForMultipleNamespacedSubRequest()
    {
        $expectedArguments = [
            'SubNamespace' => [
                'arg1' => 'val1',
                '@action' => 'someaction',
                '@controller' => 'somecontroller',
                '@package' => 'somepackage',
                'SubSubNamespace' => [
                    'arg1' => 'val1',
                    '@action' => 'someaction',
                    '@controller' => 'somecontroller',
                    '@package' => 'somepackage'
                ]
            ]
        ];
        $this->mockMainRequest->method('getArguments')->willReturn(([]));
        $this->mockSubRequest->method('getArguments')->willReturn(([
            'arg1' => 'val1',
            '@action' => 'someaction',
            '@controller' => 'somecontroller',
            '@package' => 'somepackage'
        ]));
        $this->mockSubSubRequest->method('getArgumentNamespace')->willReturn(('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->mockSubSubRequest->method('getArgumentNamespace')->willReturn((''));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault()
    {
        $expectedArguments = ['Foo' => 'Bar'];
        $this->mockMainRequest->expects($this->never())->method('getArguments');

        $this->uriBuilder->setArguments(['Foo' => 'Bar']);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet()
    {
        $expectedArguments = ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled'];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn((['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));

        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);

        $this->uriBuilder->build();
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsOfCurrentRequestIfAddQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled']];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn((['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']]));

        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);

        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet()
    {
        $expectedArguments = ['Foo' => 'Overruled'];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn((['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));

        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersInCurrentNamespaceIfArgumentsToBeExcludedFromQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['Some' => 'Retained Arguments From Request', 'SubNamespace' => ['Foo' => 'Overruled']];
        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Retained Arguments From Request']));

        $this->mockSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubNamespace'));

        $this->mockSubRequest
            ->method('getArguments')
            ->willReturn((['Some' => ['Arguments' => 'From Request']]));

        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRootRequestArgumentsIfRequestIsOfTypeSubRequest()
    {
        $rootRequestArguments = [
            'SomeNamespace' => ['Foo' => 'From Request'],
            'Foo' => 'Bar',
            'Some' => 'Other Argument From Request'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn(($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SomeNamespace' => ['Foo' => 'From Request'],
            'Foo' => 'Overruled',
            'Some' => 'Other Argument From Request'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesArgumentsBelongingToNamespacedSubRequests()
    {
        $rootRequestArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn(($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildKeepsArgumentsBelongingToNamespacedSubRequestsIfAddQueryStringIsSet()
    {
        $rootRequestArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn(($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesArgumentsBelongingToNamespacedSubSubRequests()
    {
        $rootRequestArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn(($rootRequestArguments));
        $this->mockSubSubRequest->method('getArgumentNamespace')->willReturn(('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument'
            ],
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildKeepsArgumentsBelongingToNamespacedSubSubRequestsIfAddQueryStringIsSet()
    {
        $rootRequestArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->willReturn(($rootRequestArguments));
        $this->mockSubSubRequest->method('getArgumentNamespace')->willReturn(('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Other Argument From Request']));

        $this->mockSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Other Argument From Request']));

        $this->mockSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubNamespace'));

        $this->mockSubSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubSubNamespace'));

        $this->mockSubSubRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Other Argument From Request']));

        $this->mockSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubSubNamespace'));

        $this->mockSubSubRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildWithAddQueryStringMergesAllArgumentsAndKeepsRequestBoundariesIntact()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest
            ->method('getArguments')
            ->willReturn((['Some' => 'Other Argument From Request']));

        $this->mockSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->willReturn((['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest
            ->method('getArgumentNamespace')
            ->willReturn(('SubSubNamespace'));

        $this->mockSubSubRequest
            ->method('getArguments')
            ->willReturn((['Foo' => 'SomeArgument']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }


    /**
     * @test
     */
    public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@package' => 'RootRequestPackageKey'];
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->willReturn(('RootRequestPackageKey'));
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@subpackage' => 'RootRequestSubpackageKey'];
        $this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->willReturn(('RootRequestSubpackageKey'));
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@controller' => 'RootRequestControllerName'];
        $this->mockMainRequest->expects($this->once())->method('getControllerName')->willReturn(('RootRequestControllerName'));
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@action' => 'RootRequestActionName'];
        $this->mockMainRequest->expects($this->once())->method('getControllerActionName')->willReturn(('RootRequestActionName'));
        $this->mockMainRequest->method('getArguments')->willReturn(([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildPassesBaseUriToRouter()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame($this->mockBaseUri, $resolveContext->getBaseUri());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildAppendsSectionIfSectionIsSpecified()
    {
        $mockResolvedUri = $this->createMock(UriInterface::class);
        $mockResolvedUri->expects($this->once())->method('withFragment')->with('SomeSection')->willReturn(($mockResolvedUri));

        $this->mockRouter->expects($this->once())->method('resolve')->willReturn(($mockResolvedUri));

        $this->uriBuilder->setSection('SomeSection');
        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildDoesNotSetAbsoluteUriFlagByDefault()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertFalse($resolveContext->isForceAbsoluteUri());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildForwardsForceAbsoluteUriFlagToRouter()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertTrue($resolveContext->isForceAbsoluteUri());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setCreateAbsoluteUri(true);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildPrependsScriptRequestPathByDefaultIfCreateAbsoluteUriIsFalse()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getServerParams')->willReturn(['SCRIPT_NAME' => '/document-root/index.php']);
        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame('document-root/', $resolveContext->getUriPathPrefix());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setCreateAbsoluteUri(false);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildPrependsIndexFileIfRewriteUrlsIsOff()
    {
        $mockEnvironment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->onlyMethods(['isRewriteEnabled'])->getMock();
        $this->inject($this->uriBuilder, 'environment', $mockEnvironment);

        $this->mockRouter->expects($this->once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame('index.php/', $resolveContext->getUriPathPrefix());
            return $this->createMock(UriInterface::class);
        });

        $this->uriBuilder->setCreateAbsoluteUri(false);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function resetSetsAllOptionsToTheirDefaultValue()
    {
        $this->uriBuilder
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('someFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments']);

        $this->uriBuilder->reset();

        self::assertEquals([], $this->uriBuilder->getArguments());
        self::assertEquals('', $this->uriBuilder->getSection());
        self::assertEquals('', $this->uriBuilder->getFormat());
        self::assertEquals(false, $this->uriBuilder->getCreateAbsoluteUri());
        self::assertEquals(false, $this->uriBuilder->getAddQueryString());
        self::assertEquals([], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function setRequestResetsUriBuilder()
    {
        /** @var Mvc\Routing\UriBuilder|\PHPUnit\Framework\MockObject\MockObject $uriBuilder */
        $uriBuilder = $this->getAccessibleMock(Mvc\Routing\UriBuilder::class, ['reset']);
        $uriBuilder->expects($this->once())->method('reset');
        $uriBuilder->setRequest($this->mockMainRequest);
    }

    /**
     * @test
     */
    public function setArgumentsSetsNonPrefixedArgumentsByDefault()
    {
        $arguments = [
            'argument1' => 'argument1Value',
            'argument2' => [
                'argument2a' => 'argument2aValue'
            ]
        ];
        $this->uriBuilder->setArguments($arguments);
        $expectedResult = $arguments;
        self::assertEquals($expectedResult, $this->uriBuilder->getArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWillKeepFormatOfMainRequest()
    {
        $expectedArguments = [
            '@format' => 'custom',
            'SubNamespace' => ['@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->method('getFormat')->willReturn(('custom'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->uriFor('SomeAction', [], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithFormatWillNotOverrideFormatOfMainRequest()
    {
        $expectedArguments = [
            '@format' => 'custom',
            'SubNamespace' => ['@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'inner']
        ];
        $this->mockMainRequest->method('getFormat')->willReturn(('custom'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setFormat('inner');
        $this->uriBuilder->uriFor('SomeAction', [], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }
}
