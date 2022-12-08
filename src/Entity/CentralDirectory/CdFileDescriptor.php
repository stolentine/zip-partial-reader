<?php

namespace Stolentine\ZipPartialReader\Entity\CentralDirectory;

class CdFileDescriptor
{
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        public readonly int $size,
    ) {
    }
}