<?php

namespace Stolentine\ZipPartialReader\Entity\LocalFile;

use SplFileInfo;

interface LocalFileHeader
{
    public function getFileName(): string;

    public function getUncompressedSize(): int;

    public function getCompressedSize(): int;

    public function getFullVariableLength(): int;

    /**
     * @param resource $compressedFile
     * @param SplFileInfo $file
     * @return void
     */
    public function uncompresse($compressedFile, SplFileInfo $file): void;
}