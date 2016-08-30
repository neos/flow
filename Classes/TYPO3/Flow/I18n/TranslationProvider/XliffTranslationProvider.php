<?php
namespace TYPO3\Flow\I18n\TranslationProvider;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Cldr\Reader\PluralsReader;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Xliff\Model\FileAdapter;
use TYPO3\Flow\I18n\Xliff\Service\XliffFileProvider;

/**
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * @Flow\Scope("singleton")
 */
class XliffTranslationProvider implements TranslationProviderInterface
{

    /**
     * @Flow\Inject
     * @var XliffFileProvider
     */
    protected $fileProvider;

    /**
     * @Flow\Inject
     * @var PluralsReader
     */
    protected $pluralsReader;


    /**
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws Exception\InvalidPluralFormException
     */
    public function getTranslationByOriginalLabel($originalLabel, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $fileData = $this->fileProvider->getMergedFileData($packageKey . ':' . $sourceName, $locale);
        $fileModel = new FileAdapter($fileData, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $fileModel->getTargetBySource($originalLabel, $pluralFormIndex);
    }

    /**
     * Returns label for a key ($labelId) from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $labelId Key used to find translated label
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws Exception\InvalidPluralFormException
     */
    public function getTranslationById($labelId, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $fileData = $this->fileProvider->getMergedFileData($packageKey . ':' . $sourceName, $locale);
        $fileModel = new FileAdapter($fileData, $locale);

        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new Exception\InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033387);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $fileModel->getTargetByTransUnitId($labelId, $pluralFormIndex);
    }
}
