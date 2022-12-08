<?php

namespace Stolentine\ZipPartialReader\Entity\CentralDirectory\ExtraField;

use Stolentine\ZipPartialReader\Entity\CentralDirectory\CdFileHeader;

abstract class AbstractExtendedCdFileHeader implements CdFileHeader
{
    private readonly CdFileHeader $cdFileHeader;

    public function __construct(
        CdFileHeader $cdFileHeader
    ) {
        $this->cdFileHeader = $cdFileHeader;
    }

    public function isDir(): bool
    {
        return $this->cdFileHeader->isDir();
    }

    public function getFileName(): string
    {
        return $this->cdFileHeader->getFileName();
    }

    public function getUncompressedSize(): int
    {
        return $this->cdFileHeader->getUncompressedSize();
    }

    public function getCompressedSize(): int
    {
        return $this->cdFileHeader->getCompressedSize();
    }

    public function getRelativeOffsetLocalHeader(): int
    {
        return $this->cdFileHeader->getRelativeOffsetLocalHeader();
    }

    public function getDiskNumber(): int
    {
        return $this->cdFileHeader->getDiskNumber();
    }
}