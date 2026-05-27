<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the Locale Utility
 */
final class UtilityTest extends UnitTestCase
{
    /**
     * Data provider with valid Accept-Language headers and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleHttpAcceptLanguageHeaders(): \Iterator
    {
        yield ['pl, en-gb;q=0.8, en;q=0.7', ['pl', 'en-gb', 'en']];
        yield ['de, *;q=0.8', ['de', '*']];
        yield ['sv, wont-accept;q=0.8, en;q=0.5', ['sv', 'en']];
        yield ['de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4', ['de-DE', 'de', 'en-US', 'en']];
    }

    /**
     * @test
     * @dataProvider sampleHttpAcceptLanguageHeaders
     */
    public function httpAcceptLanguageHeadersAreParsedCorrectly($acceptLanguageHeader, array $expectedResult)
    {
        $languages = I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);
        self::assertEquals($expectedResult, $languages);
    }

    /**
     * Data provider with filenames with locale tags and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function filenamesWithLocale(): \Iterator
    {
        yield ['foobar.en_GB.ext', 'en_GB'];
        yield ['en_GB.xlf', 'en_GB'];
        yield ['foobar.ext', false];
        yield ['foobar', false];
        yield ['foobar.php.tmpl', false];
        yield ['foobar.rss.php', false];
        yield ['foobar.xml.php', false];
    }

    /**
     * @test
     * @dataProvider filenamesWithLocale
     */
    public function localeIdentifiersAreCorrectlyExtractedFromFilename($filename, $expectedResult)
    {
        $result = I18n\Utility::extractLocaleTagFromFilename($filename);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with haystack strings and needle strings, used to test
     * comparison methods. The third argument denotes whether needle is same
     * as beginning of the haystack, or it's ending, or both or none.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleHaystackStringsAndNeedleStrings(): \Iterator
    {
        yield ['teststring', 'test', 'beginning'];
        yield ['foo', 'bar', 'none'];
        yield ['baz', '', 'none'];
        yield ['foo', 'foo', 'both'];
        yield ['foobaz', 'baz', 'ending'];
    }

    /**
     * @test
     * @dataProvider sampleHaystackStringsAndNeedleStrings
     */
    public function stringIsFoundAtBeginningOfAnotherString($haystack, $needle, $comparison)
    {
        $expectedResult = ($comparison === 'beginning' || $comparison === 'both') ? true : false;
        $result = I18n\Utility::stringBeginsWith($haystack, $needle);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     * @dataProvider sampleHaystackStringsAndNeedleStrings
     */
    public function stringIsFoundAtEndingOfAnotherString($haystack, $needle, $comparison)
    {
        $expectedResult = ($comparison === 'ending' || $comparison === 'both') ? true : false;
        $result = I18n\Utility::stringEndsWith($haystack, $needle);
        self::assertEquals($expectedResult, $result);
    }
}
