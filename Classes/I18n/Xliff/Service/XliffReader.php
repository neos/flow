<?php
namespace Neos\Flow\I18n\Xliff\Service;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

/**
 * An Xliff reader
 *
 * @Flow\Scope("singleton")
 */
class XliffReader
{
    /**
     * @Flow\Inject(name="Neos.Flow:I18nLogger")
     * @var LoggerInterface
     */
    protected $i18nLogger;

    /**
     * @param string $sourcePath
     * @param callable $iterator
     * @return void
     */
    public function readFiles($sourcePath, callable $iterator)
    {
        $reader = new \XMLReader();
        $reader->open($sourcePath);
        $reader->read();

        /** @var object|\XMLReader $reader the stubs for XMLReader are wrong https://github.com/phpstan/phpstan/issues/8629 */
        if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name === 'xliff') {
            $version = $reader->getAttribute('version');
            $result = true;
            while (!$this->isFileNode($reader) && $result) {
                $result = $reader->read();
            }
            $offset = 0;
            $iterator($reader, $offset, $version);
            while ($reader->next()) {
                if ($this->isFileNode($reader)) {
                    $offset++;
                    $iterator($reader, $offset, $version);
                }
            }
        } else {
            $this->i18nLogger->info('Given source "' . $sourcePath . '" is not a valid XLIFF file', LogEnvironment::fromMethodName(__METHOD__));
        }

        $reader->close();
    }

    /**
     * @param \XMLReader $reader
     * @return boolean
     */
    protected function isFileNode(\XMLReader $reader)
    {
        /** @var object|\XMLReader $reader the stubs for XMLReader are wrong https://github.com/phpstan/phpstan/issues/8629 */
        return $reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'file';
    }
}
