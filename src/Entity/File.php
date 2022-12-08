<?php

namespace Stolentine\ZipPartialReader\Entity;

class File
{
    private const MAX_STANDARD_ZIP_SIZE = 4_294_967_295;

    public function __construct(
        private readonly int $size,
    ) {
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function hasStandardSize(): bool
    {
        return $this->size <= static::MAX_STANDARD_ZIP_SIZE;
    }
}