<?php

namespace Cubes\Uploader;

use Cubes\Uploader\Component\ComponentInterface;
use Cubes\Uploader\Component\Filesystem;
use Cubes\Uploader\Component\Finder;
use Cubes\Uploader\Http\Request;
use Cubes\Uploader\Http\Response;
use Cubes\Uploader\Http\ResponseInterface;

/**
 * Class AbstractUploader
 *
 * @package Cubes\Uploader
 */
class Uploader implements ComponentInterface
{
    /**
     * Property used to inject Filesystem component.
     *
     * @var $fs \Cubes\Uploader\Component\FileSystem
     */
    protected $fs;

    /**
     * Property used to inject Finder component.
     *
     * @var $finder \Cubes\Uploader\Component\Finder
     */
    protected $finder;

    /**
     * Property used as instance for current request.
     *
     * @var \Cubes\Uploader\Http\Request
     */
    protected $request;

    /**
     * Property used as instance for response.
     *
     * @var \Cubes\Uploader\Http\Response
     */
    protected $response;

    /**
     * @var \Cubes\Uploader\UploadHandler $handler
     */
    protected $handler;

    /**
     * Temporary filesystem directory.
     *
     * @var string $tmpDirectory
     */
    protected $tmpDirectory;

    /**
     * Filesystem upload directory.
     *
     * @var string $uploadDirectory
     */
    protected $uploadDirectory;

    /**
     * Chunk filename delimiter.
     *
     * @var string $chunkFilenameDelimiter
     */
    protected $chunkFilenameDelimiter = '.part';

    /**
     * Array of allowed types to be uploaded to server.
     *
     * @var array
     */
    protected $allowedMimeTypes = [
        'application/mp4',
        'video/mp4',
        'video/x-msvideo'
    ];

    /**
     * Regex for final file name to be filtered.
     *
     * @var string $renameRegex
     */
    protected $renameRegexPattern;

    /**
     * Final file name.
     *
     * @var string $finalFileName
     */
    protected $finalFileName;

    /**
     * AbstractUploader constructor.
     *
     * @param  string $tmpDirectory      | Temporary directory where every chunk will be stored.
     * @param  string $uploadDirectory   | Final upload directory where merged files will be stored.
     * @param  array  $allowedMimeTypes  | Allowed file mime types.
     * @param  string $renameRegex       | Rename regex used to rename final filename (optional).
     * @param  string $finalFileName     | Exact name of final merged file.
     * @param  string $type              | Type parameter used to identify if we are working with Resumable.js
     * @return mixed                     |
     */
    public function __construct(
        $tmpDirectory       = '',
        $uploadDirectory    = '',
        $allowedMimeTypes   = [],
        $renameRegex        = '',
        $finalFileName      = '',
        $type               = null)
    {
        $this->request = new Request();
        $this->response = new Response();

        // Set required parameters.
        $this->buildComponents();
        $this->defineProperties(
            $tmpDirectory,     $uploadDirectory,
            $allowedMimeTypes, $renameRegex,
            $finalFileName,    $type
        );
    }

    /**
     * Method buildComponents used for injecting components into container,
     * so we can use it in inherit classes.
     *
     * @return mixed
     */
    public function buildComponents()
    {
        $this->fs = new Filesystem();
        $this->finder = new Finder();
        $this->handler = new UploadHandler();
    }

    /**
     * Method defineProperties used to define class properties
     * to values that came from __construct.
     *
     * @param  $tmpDirectory
     * @param  $uploadDirectory
     * @param  $allowedMimeTypes
     * @param  $renameRegex
     * @param  $finalFileName
     * @param  $type
     * @return $this
     */
    protected function defineProperties(
        $tmpDirectory,     $uploadDirectory,
        $allowedMimeTypes, $renameRegex,
        $finalFileName,    $type)
    {
        $this
            ->setTmpDirectory($tmpDirectory)
            ->setUploadDirectory($uploadDirectory)
            ->setAllowedMimeTypes(!empty($allowedMimeTypes) ?: $this->getAllowedMimeTypes())
            ->setRenameRegexPattern($renameRegex)
            ->setFinalFileName($finalFileName)
            ->setHandlerType($type)
        ;

        return $this;
    }

    /**
     * Method handle used to handle/receive/merge/move chunked files.
     *
     * @return  mixed
     */
    public function handle()
    {
        // If UploadHandler accessor is defined
        // we will rebuild all request params to defined value.
        if ($this->getHandler()->getAccessor()) {
            $this->normalizeHandlerParams();
        }

        $request = $this->getRequest();
        if (!empty($request->getParameters())) {
            if (!empty($request->getFiles())) {
                return $this->receiveChunks();
            } else {
                return $this->receiveTestChunks();
            }
        }
    }

    protected function receiveChunks()
    {
        // Get Request and handler than
        // Fetch files from request and
        // required parameters.
        $request     = $this->getRequest();
        $handler     = $this->getHandler();
        $file        = $request->getFiles();
        $identifier  = $handler->getIdentifier();
        $fileName    = $handler->getFilename();
        $chunkNumber = $handler->getChunkNumber();

        // Check if chunk finished partial file upload
        // If that is the case move partial to destination partial directory.
        if (!$this->isChunkUploaded($identifier, $fileName, $chunkNumber)) {
            $tmpChunkDirectory = $this->getTmpChunkDirectory($identifier);
            $tmpChunkFileName  = $this->getTmpChunkFilename($fileName, $chunkNumber);
            $chunkFile = $tmpChunkDirectory .'/'. $tmpChunkFileName;
            $this->move($file['tmp_name'], $chunkFile);
        }

        // If all chunks are uploaded we can merge all chunks
        // remove tmp chunk directory and send 201 status code as Response.
        $chunkSize   = $handler->getChunkSize();
        $totalSize   = $handler->getTotalSize();
        if ($this->isUploaded($fileName, $identifier, $chunkSize, $totalSize)) {
            $finishedStatus = $this->make($identifier, $fileName);
            if ($finishedStatus) {
                return $this->getResponse()->send(201);
            }
        }

        return $this->getResponse()->send(201);
    }

    /**
     * Method receiveTestChunks used to allow uploads to be resumed after browser
     * restarts and even across browsers.
     * In theory you could even run the same file upload
     * across multiple tabs or different browsers,
     * If this request returns a 200 HTTP code, the chunks
     * is assumed to have been completed.
     * If the request returns anything else, the chunk will
     * be uploaded in the standard fashion.
     *
     * @return int
     */
    protected function receiveTestChunks()
    {
        $handler = $this->getHandler();
        if (!$this->isChunkUploaded(
            $handler->getIdentifier(),
            $handler->getFilename(),
            $handler->getChunkNumber())) {
            return $this->getResponse()->send(204);
        }

        return $this->getResponse()->send(200);
    }

    /**
     * Method makeFile used to build file from chunks.
     *
     * @param  $identifier
     * @param  $filename
     *
     * @throws \Exception
     *
     * @return boolean
     */
    protected function make($identifier, $filename)
    {
        $tmpChunkDirectory = $this->getTmpChunkDirectory($identifier);
        $sort = $this->sort();
        $this->finder->files()->in($tmpChunkDirectory)->sort($sort);
        $status = false;
        $finalFileName = $this->getUploadDirectory() .'/'. $filename;
        if ($this->merge($this->finder, $finalFileName)) {
            $this->fs->remove($tmpChunkDirectory);
            if ($this->fs->exists($finalFileName)) {
                $status = true;
            }
        }
        return $status;
    }

    /**
     * Method move used to move merged file to defined upload destination.
     *
     * @param  $file
     * @param  $finalFile
     * @return boolean
     */
    protected function move($file, $finalFile)
    {
        $status = false;
        if ($this->fs->exists($file)) {
            $this->fs->copy($file, $finalFile);
            $status = $this->fs->exists($finalFile);
        }

        return $status;
    }

    /**
     * Method merge used to merge chunk files to single one.
     *
     * @param  \Cubes\Uploader\Component\Finder $chunkFiles
     * @param  string                           $finalFile
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function merge($chunkFiles, $finalFile)
    {
        $finalFileExt = $this->getFileExtension($finalFile);
        $finalFile = $this->rename($finalFile, $finalFileExt);
        foreach ($chunkFiles as $chunkFile) {
            $this->fs->appendToFile($finalFile, $chunkFile->getContents());
        }

        // If mime type of file is not allowed
        if (!$this->isMimeAllowed($finalFile)) {
            $this->fs->remove($finalFile);
            throw new \Exception('Uploaded file mime type is not allowed.');
        }

        // Return status true/false.
        $status = $this->fs->exists($finalFile);
        return $status;
    }

    /**
     * Method used to rename file if needed and depending on the properties status.
     *
     * @param  $finalFile
     * @param  $finalFileExt
     * @return mixed|string
     */
    protected function rename($finalFile, $finalFileExt)
    {
        // First we will remove extension from file so we
        // can work with the filename independently.
        $finalFile = str_replace(
            $this->getFileExtension($finalFile), '', $finalFile
        );

        // Check if rename regex is set
        // so we can apply it on file.
        $regexPattern = $this->getRenameRegexPattern();
        if (!empty($regexPattern)) {
            if (is_array($regexPattern)) {
                $pattern = $regexPattern['pattern'];
                $replacement = $regexPattern['replacement'];
                $finalFile = preg_replace($pattern, $replacement, $finalFile);
            } else {
                $finalFile = preg_replace($this->getRenameRegexPattern(), '', $finalFile);
            }
        }

        // Check if file name is defined exactly and apply it.
        $finalFileName = $this->getFinalFileName();
        if (!empty($finalFileName)) {
            $oldFilePath = $finalFile;
            $finalFile   = substr($finalFile, strrpos($finalFile, '/') + 1);
            $finalFile   = str_replace($finalFile, '', $oldFilePath);
            return $finalFile . $finalFileName .'.'. $finalFileExt;
        }

        return $finalFile . $finalFileExt;
    }

    /**
     * Returns exact extension of file.
     *
     * @param  $fileName
     * @return mixed
     */
    protected function getFileExtension($fileName)
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    /**
     * Returns true if passed file mime type is in allowedMimeTypes.
     *
     * @param  $file
     *
     * @throws \Exception - If finfo function doesn't exist.
     *
     * @return bool
     */
    protected function isMimeAllowed($file)
    {
        if (function_exists('finfo_file')) {
            if (in_array(mime_content_type($file), $this->getAllowedMimeTypes())) {
                return true;
            }
        } else {
            throw new \Exception('finfo extension is not enabled on your server.');
        }

    }

    /**
     * Method sort used as closure to sort chunked files by natsort compare so
     * we don't have conflict when merging files because of incorrect file order.
     */
    protected function sort()
    {
        return function (\SplFileInfo $a, \SplFileInfo $b) {
            return strnatcmp($a->getRealPath(), $b->getRealPath());
        };
    }

    /**
     * Method isUploaded checks if file is uploaded.
     *
     * @param  string       $filename
     * @param  string       $identifier
     * @param  int|string   $chunkSize
     * @param  int|string   $totalSize
     * @return bool
     */
    protected function isUploaded($filename, $identifier, $chunkSize, $totalSize)
    {
        if ($chunkSize <= 0) {
            return false;
        }

        $numOfChunks = intval($totalSize / $chunkSize) + ($totalSize % $chunkSize == 0 ? 0 : 1);
        for ($i = 1; $i < $numOfChunks; $i++) {
            if (!$this->isChunkUploaded($identifier, $filename, $i)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method isChunkUploaded checks if chunk finished partial upload.
     *
     * @param  string         $identifier
     * @param  string         $fileName
     * @param  integer|string $chunkNumber
     * @return bool
     */
    protected function isChunkUploaded($identifier, $fileName, $chunkNumber)
    {
        $directory = $this->getTmpChunkDirectory($identifier);
        $tmpChunkFileName = $this->getTmpChunkFilename($fileName, $chunkNumber);
        if ($this->fs->exists($directory .'/'. $tmpChunkFileName)) {
            return true;
        }
        return false;
    }

    /**
     * Returns temporary chunk directory.
     *
     * @param $identifier
     * @return string
     */
    protected function getTmpChunkDirectory($identifier)
    {
        $directory = $this->getTmpDirectory() .'/'. $identifier;
        if (!$this->fs->exists($directory)) {
            $this->fs->mkdir($directory, 0777);
        }

        return $directory;
    }

    /**
     * Returns temporary chunk filename.
     *
     * @param  $name
     * @param  $chunkNumber
     * @return string
     */
    protected function getTmpChunkFilename($name, $chunkNumber)
    {
        return $name .$this->getChunkFilenameDelimiter(). $chunkNumber;
    }

    /**
     * Method normalizeHandlerParams used to rebuild
     * UploadHandler parameter names from request data.
     *
     * We normalize data because of Resumable.js library.
     * If original request param was 'resumableIdentifier' we'll
     * redefine it to just 'identifier'.
     */
    protected function normalizeHandlerParams()
    {
        $handler = $this->getHandler();
        $parameters = $this->getRequest()->getParameters();
        foreach ($parameters as $parameterName => $parameterValue) {
            $normalizedAccessor = str_replace($handler->getAccessor(), '', $parameterName);
            $parameterName = lcfirst(ucwords($normalizedAccessor));
            if (array_key_exists($parameterName, $handler->getParameters())) {
                $this->handler->setParameter($parameterName, $parameterValue);
            }
        }
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param  Request $request
     * @return \Cubes\Uploader\Uploader
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param  ResponseInterface $response
     * @return \Cubes\Uploader\Uploader
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return string
     */
    public function getTmpDirectory()
    {
        return $this->tmpDirectory;
    }

    /**
     * @param string $tmpDirectory
     * @return \Cubes\Uploader\Uploader
     */
    public function setTmpDirectory($tmpDirectory)
    {
        $this->tmpDirectory = $tmpDirectory;
        return $this;
    }

    /**
     * @return string
     */
    public function getUploadDirectory()
    {
        return $this->uploadDirectory;
    }

    /**
     * @param string $uploadDirectory
     * @return \Cubes\Uploader\Uploader
     */
    public function setUploadDirectory($uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
        return $this;
    }

    /**
     * @return string
     */
    public function getChunkFilenameDelimiter()
    {
        return $this->chunkFilenameDelimiter;
    }

    /**
     * @param  string $chunkFilenameDelimiter
     * @return Uploader
     */
    public function setChunkFilenameDelimiter($chunkFilenameDelimiter)
    {
        $this->chunkFilenameDelimiter = $chunkFilenameDelimiter;
        return $this;
    }

    /**
     * @return UploadHandler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param  UploadHandler $handler
     * @return Uploader
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @param  $type
     * @return $this
     */
    public function setHandlerType($type)
    {
        $this->handler->setAccessor($type);
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param array $allowedMimeTypes
     * @return Uploader
     */
    public function setAllowedMimeTypes(array $allowedMimeTypes)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
        return $this;
    }

    /**
     * @param $allowedMimeType
     * @return $this
     */
    public function addAllowedMimeType($allowedMimeType)
    {
        $this->allowedMimeTypes[] = $allowedMimeType;
        return $this;
    }

    /**
     * @return string
     */
    public function getRenameRegexPattern()
    {
        return $this->renameRegexPattern;
    }

    /**
     * @param  string|array $renameRegex
     * @return Uploader
     */
    public function setRenameRegexPattern($renameRegex)
    {
        $this->renameRegexPattern = $renameRegex;
        return $this;
    }

    /**
     * @return string
     */
    public function getFinalFileName()
    {
        return $this->finalFileName;
    }

    /**
     * @param string $finalFileName
     * @return Uploader
     */
    public function setFinalFileName($finalFileName)
    {
        $this->finalFileName = $finalFileName;
        return $this;
    }
}