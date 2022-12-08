<?php

namespace Stolentine\ZipPartialReader\Entity\LocalFile\ExtraField;

use Stolentine\ZipPartialReader\Entity\LocalFile\LocalFileHeader;
use SplFileInfo;

abstract class AbstractExtendedLocalFileHeader implements LocalFileHeader
{
    private readonly LocalFileHeader $localFileHeader;

    public function __construct(
        LocalFileHeader $localFileHeader
    ) {
        $this->localFileHeader = $localFileHeader;
    }

    public function getFileName(): string
    {
        return $this->localFileHeader->getFileName();
    }

    public function getUncompressedSize(): int
    {
        return $this->localFileHeader->getUncompressedSize();
    }

    public function getCompressedSize(): int
    {
        return $this->localFileHeader->getCompressedSize();
    }

    public function getFullVariableLength(): int
    {
        return $this->localFileHeader->getFullVariableLength();
    }

    public function uncompresse($compressedFile, SplFileInfo $file): void
    {
        $this->localFileHeader->uncompresse($compressedFile, $file);
    }
}