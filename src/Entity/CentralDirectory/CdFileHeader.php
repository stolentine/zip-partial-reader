<?php

namespace Stolentine\ZipPartialReader\Entity\CentralDirectory;

interface CdFileHeader
{
    public function isDir(): bool;

    public function getFileName(): string;

    public function getUncompressedSize(): int;

    public function getCompressedSize(): int;

    public function getRelativeOffsetLocalHeader(): int;

    public function getDiskNumber(): int;
}