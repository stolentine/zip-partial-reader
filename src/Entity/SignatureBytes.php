<?php

namespace Stolentine\ZipPartialReader\Entity;

use Stolentine\ZipPartialReader\Exception\SignatureNotFoundException;

class SignatureBytes
{
    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function fromByte(string $bytes)
    {
        return new static($bytes);
    }

    public function startsWithSignature(string $bytes): bool
    {
        return str_starts_with($bytes, $this->value);
    }

    public function getBytesAfterSignature(string $bytes): string
    {
        $result = strstr($bytes, $this->value);

        if ($result === false) { // maybe wrong cast
            throw new SignatureNotFoundException();
        }

        return $result;
    }

    public function toBytes(): string
    {
        return $this->value;
    }
}