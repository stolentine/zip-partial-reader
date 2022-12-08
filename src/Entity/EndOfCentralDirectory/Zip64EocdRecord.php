<?php

namespace Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

/**
 * Zip64 end of central directory record
 */
class Zip64EocdRecord
{
    private const SIGNATURE_BYTES = "\x50\x4b\x06\x06"; //
    private const HEADER_LENGTH = 56; // bytes

    /**
     * signature + size
     */
    private const LEADING_BYTES = 12; // bytes

    /**
     * zip64 end of central dir signature
     *
     * 4 bytes  (0x07064b50)
     */
    private readonly int $signature;

    /**
     * size of zip64 end of central directory record
     *
     * 8 bytes
     *
     * 4.3.14.1 The value stored into the "size of zip64 end of central
     * directory record" SHOULD be the size of the remaining
     * record and SHOULD NOT include the leading 12 bytes.
     */
    private readonly int $size;

    /**
     * version made by
     *
     * 2 bytes
     */
    private readonly int $versionMadeBy;

    /**
     * version needed to extract
     *
     * 2 bytes
     */
    private readonly int $versionNeededToExtract;

    /**
     * number of this disk
     *
     * 4 bytes
     */
    private readonly int $numberDisk;

    /**
     * number of the disk with the start of the central directory
     *
     * 4 bytes
     */
    private readonly int $numberDiskWithStartCd;

    /**
     * total number of entries in the central directory on this disk
     *
     * 8 bytes
     */
    private readonly int $entriesCountInCdOnDisk;

    /**
     * total number of entries in the central directory
     *
     * 8 bytes
     */
    private readonly int $totalEntriesCountInCd;

    /**
     * size of the central directory
     *
     * 8 bytes
     */
    private readonly int $sizeCd;

    /**
     * offset of start of central directory with respect to the starting disk number
     *
     * 8 bytes
     */
    private readonly int $offsetCd;


    /**
     * todo what is it?
     *
     * zip64 extensible data sector    (variable size)
     */
    private readonly string $extensibleData;


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
        $instance->size = $bytes->long();
        $instance->versionMadeBy = $bytes->short();
        $instance->versionNeededToExtract = $bytes->short();
        $instance->numberDisk = $bytes->int();
        $instance->numberDiskWithStartCd = $bytes->int();
        $instance->entriesCountInCdOnDisk = $bytes->long();
        $instance->totalEntriesCountInCd = $bytes->long();
        $instance->sizeCd = $bytes->long();
        $instance->offsetCd = $bytes->long();

        $extensibleDataLength = $instance->size + static::LEADING_BYTES - static::HEADER_LENGTH;
        $instance->extensibleData = $bytes->string($extensibleDataLength);

        /** todo
            4.3.14.3 Special purpose data MAY reside in the zip64 extensible
            data sector field following either a V1 or V2 version of this
            record.  To ensure identification of this special purpose data
            it MUST include an identifying header block consisting of the
            following:

            Header ID  -  2 bytes
            Data Size  -  4 bytes

            The Header ID field indicates the type of data that is in the
            data block that follows.

            Data Size identifies the number of bytes that follow for this
            data block type.
         */

        return $instance;
    }

    public function getOffsetCd(): int
    {
        return $this->offsetCd;
    }

    public function getSizeCd(): int
    {
        return $this->sizeCd;
    }
}