<?php

namespace SpareMusic\ResumableJS;

use SplFileInfo;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\Filesystem;

class Resumable
{
    /** Used for checking current chunk's status. */
    const MODE_TEST_CHUNK = 1;

    /** Used for uploading current chunk. */
    const MODE_UPLOAD_CHUNK = 2;

    protected $fs;
    protected $statusCode = 500;
    protected $filename = null;
    protected $uploadPath;
    protected $chunkPath;
    protected $validator;
    protected $resumableParameters;
    protected $mode = null;
    protected $request;
    protected $completed = false;

    public function __construct(Request $request, $uploadPath = null)
    {
        $this->fs = new Filesystem();
        if ($uploadPath) {
            $this->setUploadPath($uploadPath);
        }
        $this->request = $request;
        $this->resumableParameters = new ResumableParameters($this->request);
    }

    /**
     * Handles processing of resumable.js requests.
     *
     * @return \SpareMusic\ResumableJS\Resumable
     */
    public function process()
    {
        $mode = $this->getMode();
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $this->resumableParameters->getIdentifier();
        $chunkFile = $chunkPath . DIRECTORY_SEPARATOR . $this->getFilename() . '.part' . $this->resumableParameters->getChunkNumber();
        if (self::MODE_TEST_CHUNK === $mode) {
            if ($this->fs->exists($chunkFile)) {
                return $this->setStatusCode(200);
            } else {
                return $this->setStatusCode(204);
            }
        } elseif (self::MODE_UPLOAD_CHUNK === $mode) {
            /** @var UploadedFile $file */
            foreach ($this->request->files as $file) {
                if (UPLOAD_ERR_OK !== $file->getError()) {
                    continue;
                }

                if (!$this->getValidator()($file, $this->getResumableParameters())) {
                    return $this->setStatusCode(415);
                }

                if (!$this->fs->exists($chunkPath)) {
                    try {
                        // create our chunk directory
                        $this->fs->makeDirectory($chunkPath, 0755);
                    } catch (\ErrorException $e) {

                    }
                }
                // move the uploaded file
                $file->move($chunkPath, $this->getFilename() . '.part' . $this->resumableParameters->getChunkNumber());
            }
        }
        if ($this->validateChunks()) {
            $this->createFileFromChunks();
        }
        return $this->setStatusCode(200);
    }

    /**
     * Check if all needed chunks exist inside the chunk path directory
     *
     * @return bool
     */
    protected function validateChunks()
    {
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $this->resumableParameters->getIdentifier();
        if (!is_dir($chunkPath)) {
            return false;
        }
        $totalChunkedSize = 0;
        for ($i = 1; $i <= $this->resumableParameters->getTotalChunks(); $i++) {
            $chunkFile = $chunkPath . DIRECTORY_SEPARATOR . $this->getFilename() . '.part' . $i;
            $file = new SplFileInfo($chunkFile);
            if ($file->isFile()) {
                $totalChunkedSize += $file->getSize();
            }
        }
        return $totalChunkedSize >= $this->resumableParameters->getSize();
    }

    /**
     * Assembles chunks that have been uploaded. Based on Chris Gregory's code found inside the samples folder of
     * resumable.js (https://github.com/23/resumable.js/blob/master/samples/Backend%20on%20PHP.md)
     */
    protected function createFileFromChunks()
    {
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $this->resumableParameters->getIdentifier();
        if (!is_dir($this->getUploadPath())) {
            throw new RuntimeException('Upload path does not exist: ' . $this->getUploadPath());
        }
        if (($fp = fopen($this->getUploadPath() . DIRECTORY_SEPARATOR . $this->getFilename(true), 'w')) !== false) {
            for ($i = 1; $i <= $this->resumableParameters->getTotalChunks(); $i++) {
                $chunkFile = $chunkPath . DIRECTORY_SEPARATOR . $this->getFilename() . '.part' . $i;
                fwrite($fp, file_get_contents($chunkFile));
            }
            fclose($fp);
        } else {
            return false;
        }

        $this->fs->deleteDirectory($chunkPath);

        $this->completed = true;
    }

    /**
     * Respond to HTTP request
     *
     * @param string $message
     *
     * @return mixed
     */
    public function respond($message = "")
    {
        return response($message, $this->getStatusCode());
    }

    /**
     * Get full file path
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->getUploadPath() . "/" . $this->getFilename(true);
    }

    /**
     * @param bool $ext
     *
     * @return mixed
     */
    public function getFilename($ext = false)
    {
        if (empty($this->filename)) {
            return $this->resumableParameters->getFilename();
        }

        if (!$ext) {
            return $this->filename;
        }

        return $this->filename . "." . $this->getExtenstion($this->resumableParameters->getFilename());
    }

    /**
     * @param mixed $filename
     *
     * @return \SpareMusic\ResumableJS\Resumable
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function removeExtension($filename)
    {
        $parts = explode('.', basename($filename));
        $ext = end($parts); // get extension

        // remove extension from filename if any
        return str_replace(sprintf('.%s', $ext), '', $filename);
    }

    public function getExtenstion($filename)
    {
        $parts = explode('.', basename($filename));
        return end($parts); // get extension
    }

    /**
     * @param mixed $validator
     *
     * @return Resumable
     */
    public function setValidator(callable $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validator ?: function () {
            return true;
        };
    }

    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
        if (!$this->fs->exists($this->uploadPath)) {
            $this->fs->makeDirectory($this->uploadPath, 0777, true, true);
        }
        return $this;
    }

    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    public function setChunkPath($chunkPath)
    {
        $this->chunkPath = $chunkPath;
        if (!$this->fs->exists($this->chunkPath)) {
            $this->fs->makeDirectory($this->chunkPath, 0777, true, true);
        }

        return $this;
    }

    public function getChunkPath()
    {
        if (!$this->chunkPath) {
            return sys_get_temp_dir();
        } else {
            return $this->chunkPath;
        }
    }

    public function setMode($mode)
    {
        if (self::MODE_UPLOAD_CHUNK === $mode || self::MODE_TEST_CHUNK === $mode) {
            $this->mode = $mode;
        } else {
            $this->mode = self::MODE_TEST_CHUNK;
        }
    }

    public function getMode()
    {
        if (!empty($this->mode)) {
            return $this->mode;
        }

        if ($this->request->isMethod('post')) {
            return self::MODE_UPLOAD_CHUNK;
        }
        return self::MODE_TEST_CHUNK;
    }

    public function setResumableParameters(ResumableParameters $resumableParameters)
    {
        $this->resumableParameters = $resumableParameters;
    }

    /**
     * Extracts parameters found inside $this->resumableParameters
     *
     * @return object
     */
    public function getResumableParameters()
    {
        if (!$this->resumableParameters) {
            $this->resumableParameters = new ResumableParameters($this->request);
        }
        return $this->resumableParameters;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return \SpareMusic\ResumableJS\Resumable
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->completed;
    }

    public function setup()
    {
        //
    }
}