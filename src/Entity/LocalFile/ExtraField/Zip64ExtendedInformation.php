<?php

namespace Stolentine\ZipPartialReader\Entity\LocalFile\ExtraField;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\CdFileHeader;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\StaticCdFileHeader;
use Stolentine\ZipPartialReader\Entity\LocalFile\LocalFileHeader;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

/**
 *  4.5.3 -Zip64 Extended Information Extra Field (0x0001):
 *
 *  The following is the layout of the zip64 extended
 *  information "extra" block. If one of the size or
 *  offset fields in the Local or Central directory
 *  record is too small to hold the required data,
 *  a Zip64 extended information record is created.
 *  The order of the fields in the zip64 extended
 *  information record is fixed, but the fields MUST
 *  only appear if the corresponding Local or Central
 *  directory record field is set to 0xFFFF or 0xFFFFFFFF.
 *
 *  Note: all fields stored in Intel low-byte/high-byte order.
 *
 *  Value      Size       Description
 *  -----      ----       -----------
 *  (ZIP64) 0x0001     2 bytes    Tag for this "extra" block type
 *  Size       2 bytes    Size of this "extra" block
 *  Original
 *  Size       8 bytes    Original uncompressed file size
 *  Compressed
 *  Size       8 bytes    Size of compressed data
 *  Relative Header
 *  Offset     8 bytes    Offset of local header record
 *  Disk Start
 *  Number     4 bytes    Number of the disk on which
 *  this file starts
 *
 *  This entry in the Local header MUST include BOTH original
 *  and compressed file size fields. If encrypting the
 *  central directory and bit 13 of the general purpose bit
 *  flag is set indicating masking, the value stored in the
 *
 */
class Zip64ExtendedInformation extends AbstractExtendedLocalFileHeader
{
    public const HEADER_ID = 0x0001;
    private const HEADER_LENGTH = 4; // bytes without extra fields

    private const SHORT_EXTEND_MARKER = 0xFFFF;
    private const INT_EXTEND_MARKER = 0xFFFFFFFF;

    /**
     * Size of this "extra" block
     *
     * 2 bytes
     */
    private readonly int $sizeData;

    /**
     * Original uncompressed file size
     *
     * 8 bytes
     */
    private ?int $originalSize = null;

    /**
     * Size of compressed data
     *
     * 8 bytes
     */
    private ?int $compressedSize = null;

    public function __construct(LocalFileHeader $localFileHeader)
    {
        parent::__construct($localFileHeader);
    }

    public static function fromBytes(ByteBuffer $bytes, LocalFileHeader $localFileHeader): static
    {
        if ($bytes->short() !== static::HEADER_ID) {
            throw new ZipPartialReaderException("Invalid bytes string. Must be start vis signature: " . static::HEADER_ID);
        }

        $instance = new static($localFileHeader);
        $instance->sizeData = $bytes->short();
        $bytesRead = 0;

        $needReadOriginalSize = self::hasExtendMarkerInt($localFileHeader->getUncompressedSize())
            && $instance->sizeData - $bytesRead >= ByteBuffer::LONG_LENGTH;

        if ($needReadOriginalSize) {
            $instance->originalSize = $bytes->long();
            $bytesRead += ByteBuffer::LONG_LENGTH;
        }


        $needReadCompressedSize = self::hasExtendMarkerInt($localFileHeader->getCompressedSize())
            && $instance->sizeData - $bytesRead >= ByteBuffer::LONG_LENGTH;

        if ($needReadCompressedSize) {
            $instance->compressedSize = $bytes->long();
        }

        return $instance;
    }

    protected static function hasExtendMarkerShort(int $value): bool
    {
        return $value === self::SHORT_EXTEND_MARKER;
    }

    protected static function hasExtendMarkerInt(int $value): bool
    {
        return $value === self::INT_EXTEND_MARKER;
    }

    public function getUncompressedSize(): int
    {
        return $this->originalSize ?? parent::getUncompressedSize();
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize ?? parent::getCompressedSize();
    }
}