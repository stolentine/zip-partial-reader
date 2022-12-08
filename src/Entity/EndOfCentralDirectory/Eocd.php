<?php

namespace Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\SignatureBytes;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

/**
 * End Of Central Directory
 */
class Eocd
{
    private const SIGNATURE_BYTES = "\x50\x4b\x05\x06"; //PK
    private const HEADER_LENGTH = 22; // bytes without comment

    /**
     * end of central dir signature
     *
     * 4 bytes  (0x06054b50)
     */
    private readonly int $signature;

    /**
     * number of this disk
     *
     * 2 bytes
     */
    private readonly int $diskNumber;

    /**
     * number of the disk with the start of the central directory
     *
     * 2 bytes
     */
    private readonly int $diskNumberWithStartOfCD;

    /**
     * total number of entries in the central directory on this disk
     *
     * 2 bytes
     */
    private readonly int $entriesCountOnDiskInCD;

    /**
     * total number of entries in the central directory
     *
     * 2 bytes
     */
    private readonly int $totalEntriesCountInCD;

    /**
     * size of the central directory
     *
     * 4 bytes
     */
    private readonly int $sizeOfCD;

    /**
     * offset of start of central directory with respect to the starting disk number
     *
     * 4 bytes
     */
    private readonly int $offsetStartOfCD;

    /**
     * .ZIP file comment length
     *
     * 2 bytes
     */
    private readonly int $commentLength;

    /**
     * .ZIP file comment
     *
     * (variable size)
     */
    private readonly string $comment;


    private function __construct()
    {
    }

    public static function fromBytes(ByteBuffer $bytes): Eocd
    {
        if (!$bytes->nextStringEquals(static::SIGNATURE_BYTES)) {
            throw new ZipPartialReaderException("Invalid bytes string. Must be start vis signature: ".static::SIGNATURE_BYTES);
        }

        $instance = new Eocd();
        $instance->signature = $bytes->int(); //101010256
        $instance->diskNumber = $bytes->short();
        $instance->diskNumberWithStartOfCD = $bytes->short();
        $instance->entriesCountOnDiskInCD = $bytes->short();
        $instance->totalEntriesCountInCD = $bytes->short();
        $instance->sizeOfCD = $bytes->int();
        $instance->offsetStartOfCD = $bytes->int();
        $instance->commentLength = $bytes->short();
        $instance->comment = $bytes->string($instance->commentLength);

        return $instance;
    }

    public function getTotalLength(): int
    {
        return static::getHeaderLength() + $this->commentLength;
    }

    public static function getHeaderLength(): int
    {
        return static::HEADER_LENGTH;
    }

    public static function getSignatureBytes(): SignatureBytes
    {
        return SignatureBytes::fromByte(static::SIGNATURE_BYTES);
    }

    public function getSizeCd(): int
    {
        return $this->sizeOfCD;
    }

    public function getOffsetCd(): int
    {
        return $this->offsetStartOfCD;
    }
}