<?php
namespace Neos\Flow\ResourceManagement\Streams;

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
use Psr\Http\Message\UriInterface;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\Exception as ResourceException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Files;

/**
 * A stream wrapper for resources.
 */
class ResourceStreamWrapper implements StreamWrapperInterface
{
    /**
     * @const string
     */
    const SCHEME = 'resource';

    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource
     */
    protected $handle;

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @Flow\Inject(lazy = false)
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * Returns the scheme ("protocol") this wrapper handles.
     *
     * @return string
     */
    public static function getScheme()
    {
        return self::SCHEME;
    }

    /**
     * Close directory handle.
     *
     * This method is called in response to closedir().
     *
     * Any resources which were locked, or allocated, during opening and use of
     * the directory stream should be released.
     *
     * @return boolean Always true
     */
    public function closeDirectory()
    {
        closedir($this->handle);
        return true;
    }

    /**
     * Open directory handle.
     *
     * This method is called in response to opendir().
     *
     * @param string $path Specifies the URL that was passed to opendir().
     * @param int $options Whether or not to enforce safe_mode (0x04).
     * @return boolean true on success or false on failure.
     */
    public function openDirectory($path, $options)
    {
        $resourceUriOrStream = $this->evaluateResourcePath($path);
        if (!is_string($resourceUriOrStream)) {
            return false;
        }
        $handle = ($resourceUriOrStream !== false) ? opendir($resourceUriOrStream) : false;
        if ($handle !== false) {
            $this->handle = $handle;
            return true;
        }
        return false;
    }

    /**
     * Read entry from directory handle.
     *
     * This method is called in response to readdir().
     *
     * @return string Should return string representing the next filename, or false if there is no next file.
     */
    public function readDirectory()
    {
        return readdir($this->handle);
    }

    /**
     * Rewind directory handle.
     *
     * This method is called in response to rewinddir().
     *
     * Should reset the output generated by dir_readdir(). I.e.: The next call
     * to dir_readdir() should return the first entry in the location returned
     * by dir_opendir().
     *
     * @return boolean always true
     */
    public function rewindDirectory()
    {
        rewinddir($this->handle);
        return true;
    }

    /**
     * Create a directory.
     *
     * This method is called in response to mkdir().
     *
     * @param string $path Directory which should be created.
     * @param integer $mode The value passed to mkdir().
     * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
     * @return void
     */
    public function makeDirectory($path, $mode, $options)
    {
        $resourceUriOrStream = $this->evaluateResourcePath($path, false);
        if (is_string($resourceUriOrStream)) {
            mkdir($resourceUriOrStream, $mode, $options&STREAM_MKDIR_RECURSIVE);
        }
    }

    /**
     * Removes a directory.
     *
     * This method is called in response to rmdir().
     *
     * Note: If the wrapper does not support creating directories it must throw
     * a \BadMethodCallException.
     *
     * @param string $path The directory URL which should be removed.
     * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
     * @return void
     * @throws \BadMethodCallException
     */
    public function removeDirectory($path, $options)
    {
        throw new \BadMethodCallException(__CLASS__ . ' does not support removeDirectory().', 1256827649);
    }

    /**
     * Renames a file or directory.
     *
     * This method is called in response to rename().
     *
     * Should attempt to rename path_from to path_to.
     *
     * @param string $source The URL to the current file.
     * @param string $target The URL which the path_from should be renamed to.
     * @return boolean true on success or false on failure.
     */
    public function rename($source, $target)
    {
        return false;
    }

    /**
     * Retrieve the underlying resource.
     *
     * This method is called in response to stream_select().
     *
     * @param integer $castType Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
     * @return resource Should return the underlying stream resource used by the wrapper, or false.
     */
    public function cast($castType)
    {
        return false;
    }

    /**
     * Close an resource.
     *
     * This method is called in response to fclose().
     *
     * All resources that were locked, or allocated, by the wrapper should be
     * released.
     *
     * @return void
     */
    public function close()
    {
        fclose($this->handle);
    }

    /**
     * Tests for end-of-file on a file pointer.
     *
     * This method is called in response to feof().
     *
     * @return boolean Should return true if the read/write position is at the end of the stream and if no more data is available to be read, or false otherwise.
     */
    public function isAtEof()
    {
        return feof($this->handle);
    }

    /**
     * Flushes the output.
     *
     * This method is called in response to fflush().
     *
     * If you have cached data in your stream but not yet stored it into the
     * underlying storage, you should do so now.
     *
     * Note: If not implemented, false is assumed as the return value.
     *
     * @return boolean Should return true if the cached data was successfully stored (or if there was no data to store), or false if the data could not be stored.
     */
    public function flush()
    {
        return true;
    }

    /**
     * Advisory file locking.
     *
     * This method is called in response to flock(), when file_put_contents()
     * (when flags contains LOCK_EX), stream_set_blocking().
     *
     * $operation is one of the following:
     *  LOCK_SH to acquire a shared lock (reader).
     *  LOCK_EX to acquire an exclusive lock (writer).
     *  LOCK_NB if you don't want flock() to block while locking.
     *
     * @param integer $operation One of the LOCK_* constants
     * @return boolean true on success or false on failure.
     */
    public function lock($operation)
    {
        return false;
    }

    /**
     * Advisory file locking.
     *
     * This method is called when closing the stream (LOCK_UN).
     *
     * @return boolean true on success or false on failure.
     */
    public function unlock()
    {
        return true;
    }

    /**
     * Opens file or URL.
     *
     * This method is called immediately after the wrapper is initialized (f.e.
     * by fopen() and file_get_contents()).
     *
     * $options can hold one of the following values OR'd together:
     *  STREAM_USE_PATH
     *    If path is relative, search for the resource using the include_path.
     *  STREAM_REPORT_ERRORS
     *    If this flag is set, you are responsible for raising errors using
     *    trigger_error() during opening of the stream. If this flag is not set,
     *    you should not raise any errors.
     *
     * @param string $path Specifies the URL that was passed to the original function.
     * @param string $mode The mode used to open the file, as detailed for fopen().
     * @param integer $options Holds additional flags set by the streams API.
     * @param string &$openedPathAndFilename If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path should be set to the full path of the file/resource that was actually opened.
     * @return boolean true on success or false on failure.
     */
    public function open($path, $mode, $options, &$openedPathAndFilename)
    {
        // w, a or x should try to create the file
        // x should fail if file exists - fopen handles that below!
        if (strpos($mode, 'w') !== false || strpos($mode, 'a') !== false || strpos($mode, 'x') !== false) {
            $resourceUriOrStream = $this->evaluateResourcePath($path, false);
        } else {
            $resourceUriOrStream = $this->evaluateResourcePath($path);
        }

        if (is_resource($resourceUriOrStream)) {
            $this->handle = $resourceUriOrStream;
            return true;
        }

        $handle = ($resourceUriOrStream !== false) ? fopen($resourceUriOrStream, $mode) : false;
        if ($handle !== false) {
            $this->handle = $handle;
            $openedPathAndFilename = $resourceUriOrStream;
            return true;
        }
        return false;
    }

    /**
     * Read from stream.
     *
     * This method is called in response to fread() and fgets().
     *
     * Note: Remember to update the read/write position of the stream (by the
     * number of bytes that were successfully read).
     *
     * @param integer $count How many bytes of data from the current position should be returned.
     * @return string If there are less than count bytes available, return as many as are available. If no more data is available, return either false or an empty string.
     */
    public function read($count)
    {
        return fread($this->handle, $count);
    }

    /**
     * Seeks to specific location in a stream.
     *
     * This method is called in response to fseek().
     *
     * The read/write position of the stream should be updated according to the
     * offset and whence .
     *
     * $whence can one of:
     *  SEEK_SET - Set position equal to offset bytes.
     *  SEEK_CUR - Set position to current location plus offset.
     *  SEEK_END - Set position to end-of-file plus offset.
     *
     * @param integer $offset The stream offset to seek to.
     * @param integer $whence
     * @return boolean true on success or false on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->handle, $offset, $whence) === 0;
    }

    /**
     * Change stream options.
     *
     * This method is called to set options on the stream.
     *
     * $option can be one of:
     *  STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
     *  STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
     *  STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
     *
     * If $option is ... then $arg1 is set to:
     *  STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
     *  STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
     *  STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
     *
     * If $option is ... then $arg2 is set to:
     *  STREAM_OPTION_BLOCKING: This option is not set.
     *  STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
     *  STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
     *
     * @param integer $option
     * @param integer $argument1
     * @param integer $argument2
     * @return boolean true on success or false on failure. If option is not implemented, false should be returned.
     */
    public function setOption($option, $argument1, $argument2)
    {
        return false;
    }

    /**
     * Retrieve the current position of a stream.
     *
     * This method is called in response to ftell().
     *
     * @return int Should return the current position of the stream.
     */
    public function tell()
    {
        return ftell($this->handle);
    }

    /**
     * Write to stream.
     *
     * This method is called in response to fwrite().
     *
     * If there is not enough room in the underlying stream, store as much as
     * possible.
     *
     * Note: Remember to update the current position of the stream by number of
     * bytes that were successfully written.
     *
     * @param string $data Should be stored into the underlying stream.
     * @return int Should return the number of bytes that were successfully stored, or 0 if none could be stored.
     */
    public function write($data)
    {
        return fwrite($this->handle, $data);
    }

    /**
     * Delete a file.
     *
     * This method is called in response to unlink().
     *
     * Note: In order for the appropriate error message to be returned this
     * method should not be defined if the wrapper does not support removing
     * files.
     *
     * @param string $path The file URL which should be deleted.
     * @return boolean true on success or false on failure.
     * @throws \BadMethodCallException
     */
    public function unlink($path)
    {
        throw new \BadMethodCallException('The package stream wrapper does not support unlink.', 1256052118);
    }

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to fstat().
     *
     * @return array See http://php.net/stat
     */
    public function resourceStat()
    {
        return fstat($this->handle);
    }

    /**
     * Retrieve information about a file.
     *
     * This method is called in response to all stat() related functions.
     *
     * $flags can hold one or more of the following values OR'd together:
     *  STREAM_URL_STAT_LINK
     *     For resources with the ability to link to other resource (such as an
     *     HTTP Location: forward, or a filesystem symlink). This flag specified
     *     that only information about the link itself should be returned, not
     *     the resource pointed to by the link. This flag is set in response to
     *     calls to lstat(), is_link(), or filetype().
     *  STREAM_URL_STAT_QUIET
     *     If this flag is set, your wrapper should not raise any errors. If
     *     this flag is not set, you are responsible for reporting errors using
     *     the trigger_error() function during stating of the path.
     *
     * Note: The stat() call is silenced through the shut-up operator because this method would issue a warning if the
     *       file does not exist - but file_exists() will call pathStat() in order to check exactly that. So without
     *       the "@" operator it wouldn't be possible to run file_exists() on a resource without issuing a warning and
     *       the resulting exception.
     *
     * @param string $path The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL. Other URL forms are not supported.
     * @param integer $flags Holds additional flags set by the streams API.
     * @return array Should return as many elements as stat() does. Unknown or unavailable values should be set to a rational value (usually 0).
     */
    public function pathStat($path, $flags)
    {
        $evaluatedResourcePath = $this->evaluateResourcePath($path);
        if (is_resource($evaluatedResourcePath)) {
            return @fstat($evaluatedResourcePath);
        }
        return @stat($evaluatedResourcePath);
    }

    /**
     * Evaluates the absolute path and filename of the resource file specified
     * by the given path.
     *
     * @param string $requestedPath
     * @param boolean $checkForExistence Whether a (non-hash) path should be checked for existence before being returned
     * @return mixed The full path and filename or false if the file doesn't exist
     * @throws \InvalidArgumentException|ResourceException
     */
    protected function evaluateResourcePath($requestedPath, $checkForExistence = true)
    {
        $requestPathParts = explode('://', $requestedPath, 2);
        if ($requestPathParts[0] !== self::SCHEME) {
            throw new \InvalidArgumentException('The ' . __CLASS__ . ' only supports the \'' . self::SCHEME . '\' scheme.', 1256052544);
        }

        if (!isset($requestPathParts[1])) {
            return false;
        }

        $resourceUriWithoutScheme = $requestPathParts[1];

        if (strpos($resourceUriWithoutScheme, '/') === false && preg_match('/^[0-9a-f]{40}$/i', $resourceUriWithoutScheme) === 1) {
            $resource = $this->resourceManager->getResourceBySha1($resourceUriWithoutScheme);
            return $this->resourceManager->getStreamByResource($resource);
        }

        list($packageName, $path) = explode('/', $resourceUriWithoutScheme, 2);

        try {
            $package = $this->packageManager->getPackage($packageName);
        } catch (\Neos\Flow\Package\Exception\UnknownPackageException $packageException) {
            throw new ResourceException(sprintf('Invalid resource URI "%s": Package "%s" is not available.', $requestedPath, $packageName), 1309269952, $packageException);
        }

        if (!$package instanceof FlowPackageInterface) {
            return false;
        }

        $resourceUri = Files::concatenatePaths([$package->getResourcesPath(), $path]);

        if ($checkForExistence === false || file_exists($resourceUri)) {
            return $resourceUri;
        }

        return false;
    }
}
