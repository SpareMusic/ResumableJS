<?php

namespace SpareMusic\ResumableJS;

use Illuminate\Http\Request;

class ResumableParameters
{
    protected $identifier = '';
    protected $filename = '';
    protected $chunkNumber = '';
    protected $size = 0;
    protected $totalChunks = 0;

    public function __construct(Request $request)
    {
        $this->populateParameters($request);
    }

    protected function populateParameters(Request $request)
    {
        if ($request->filled('resumableIdentifier')) {
            $this->identifier = trim($request->get('resumableIdentifier'));
        }
        if ($request->filled('resumableFilename')) {
            $this->filename = trim($request->get('resumableFilename'));
        }
        if ($request->filled('resumableChunkNumber')) {
            $this->chunkNumber = (int)$request->get('resumableChunkNumber');
        }
        if ($request->filled('resumableTotalSize')) {
            $this->size = (int)$request->get('resumableTotalSize');
        }
        if ($request->filled('resumableTotalChunks')) {
            $this->totalChunks = (int)$request->get('resumableTotalChunks');
        }
    }

    public function getParameters()
    {
        return (object) [
            'identifier'  => $this->identifier,
            'filename'    => $this->filename,
            'chunkNumber' => $this->chunkNumber,
            'size'        => $this->size,
            'totalChunks' => $this->totalChunks,
        ];
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getChunkNumber()
    {
        return $this->chunkNumber;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getTotalChunks()
    {
        return $this->totalChunks;
    }
}