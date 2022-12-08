<?php

namespace Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\SignatureBytes;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

/**
 * Zip64 end of central directory locator
 */
class Zip64EocdLocator
{
    private const SIGNATURE_BYTES = "\x50\x4b\x06\x07"; //
    private const HEADER_LENGTH = 20; // bytes

    /**
     * zip64 end of central dir locator signature
     *
     * 4 bytes  (0x07064b50)
     */
    private readonly int $signature;

    /**
     * number of the disk with the start of the zip64 end of central directory
     *
     * 4 bytes
     */
    private readonly int $diskNumberWithStartZip64Eocd;

    /**
     * relative offset of the zip64 end of central directory record
     *
     * 8 bytes
     */
    private readonly int $offsetZip64EocdRecord;

    /**
     * total number of disks
     *
     * 4 bytes
     */
    private readonly int $totalDiskCount;

    private function __construct()
    {
    }

    public static function fromBytes(ByteBuffer $bytes): static
    {
        if (!$bytes->nextStringEquals(static::SIGNATURE_BYTES)) {
            throw new ZipPartialReaderException("Invalid bytes string. Must be start vis signature: ".static::SIGNATURE_BYTES);
        }

        $instance = new static();
        $instance->signature = $bytes->int(); //117853008
        $instance->diskNumberWithStartZip64Eocd = $bytes->int();
        $instance->offsetZip64EocdRecord = $bytes->long();
        $instance->totalDiskCount = $bytes->int();

        return $instance;
    }


    public static function getHeaderLength(): int
    {
        return static::HEADER_LENGTH;
    }

    public function getOffsetZip64EocdRecord(): int
    {
        return $this->offsetZip64EocdRecord;
    }
}