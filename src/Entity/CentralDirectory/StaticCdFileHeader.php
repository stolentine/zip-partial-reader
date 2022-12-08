<?php

namespace Stolentine\ZipPartialReader\Entity\CentralDirectory;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\ExtraField\Zip64ExtendedInformation;
use Stolentine\ZipPartialReader\Entity\SignatureBytes;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

/**
 * Central Directory File Header
 */
class StaticCdFileHeader implements CdFileHeader
{
    private const SIGNATURE_BYTES = "\x50\x4b\x01\x02"; //PK
    private const HEADER_LENGTH = 46; // bytes without comment

    /**
     * central file header signature
     *
     * 4 bytes  (0x02014b50)
     */
    private readonly int $signature;

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
     * general purpose bit flag
     *
     * 2 bytes
     */
    private readonly int $bitFlag;

    /**
     * compression method
     *
     * 2 bytes
     */
    private readonly int $compressionMethod;

    /**
     * last mod file time
     *
     * 2 bytes
     */
    private readonly int $lastModFileTime;

    /**
     * last mod file date
     *
     * 2 bytes
     */
    private readonly int $lastModFileDate;

    /**
     * crc-32
     *
     * 4 bytes
     */
    private readonly int $crc32;

    /**
     * compressed size
     *
     * 4 bytes
     */
    private readonly int $compressedSize;

    /**
     * uncompressed size
     *
     * 4 bytes
     */
    private readonly int $uncompressedSize;

    /**
     * file name length
     *
     * 2 bytes
     */
    private readonly int $fileNameLength;

    /**
     * extra field length
     *
     * 2 bytes
     */
    private readonly int $extraFieldLength;

    /**
     * file comment length
     *
     * 2 bytes
     */
    private readonly int $fileCommentLength;

    /**
     * disk number start
     *
     * 2 bytes
     */
    private readonly int $diskNumber;

    /**
     * internal file attributes
     *
     * 2 bytes
     */
    private readonly int $internalFileAttributes;

    /**
     * external file attributes
     *
     * 4 bytes
     */
    private readonly int $externalFileAttributes;

    /**
     * relative offset of local header
     *
     * 4 bytes
     */
    private readonly int $relativeOffsetLocalHeader;

    /**
     * file name
     *
     * (variable size)
     */
    private readonly string $fileName;

    /**
     * extra field
     *
     * (variable size)
     */
    private readonly string $extraFieldBytes;

    /**
     * file comment
     *
     * (variable size)
     */
    private readonly string $fileComment;


    // 4.5.2
    private static array $extraFieldTypes = [
        Zip64ExtendedInformation::class,
//        OS2::class,
    ];

    private function __construct()
    {
    }

    public static function fromBytes(ByteBuffer $bytes): CdFileHeader
    {
        if (!$bytes->nextStringEquals(static::SIGNATURE_BYTES)) {
            throw new ZipPartialReaderException("Invalid bytes string. Must be start vis signature: " . static::SIGNATURE_BYTES);
        }

        $instance = new static();
        $instance->signature = $bytes->int();
        $instance->versionMadeBy = $bytes->short();
        $instance->versionNeededToExtract = $bytes->short();
        $instance->bitFlag = $bytes->short();
        $instance->compressionMethod = $bytes->short();
        $instance->lastModFileTime = $bytes->short();
        $instance->lastModFileDate = $bytes->short();
        $instance->crc32 = $bytes->int();
        $instance->compressedSize = $bytes->int();
        $instance->uncompressedSize = $bytes->int();
        $instance->fileNameLength = $bytes->short();
        $instance->extraFieldLength = $bytes->short();
        $instance->fileCommentLength = $bytes->short();
        $instance->diskNumber = $bytes->short();
        $instance->internalFileAttributes = $bytes->short();
        $instance->externalFileAttributes = $bytes->int();
        $instance->relativeOffsetLocalHeader = $bytes->int();

        $instance->fileName = $bytes->string($instance->fileNameLength);
        $instance->extraFieldBytes = $bytes->string($instance->extraFieldLength);
        $instance->fileComment = $bytes->string($instance->fileCommentLength);


        // todo version spec 4.5
        if ($instance->versionNeededToExtract >= 45 && $instance->extraFieldLength !== 0) {
            $extraBytes = ByteBuffer::fromBytes($instance->extraFieldBytes);

            // todo spec 4.5.2
            foreach (static::$extraFieldTypes as $type) {
                if ($extraBytes->nextShortEquals($type::HEADER_ID)) {
                    $instance = $type::fromBytes($extraBytes, $instance);
                }
            }
        }

        return $instance;
    }

    public static function getSignatureBytes(): SignatureBytes
    {
        return SignatureBytes::fromByte(static::SIGNATURE_BYTES);
    }

    public function isDir(): bool
    {
        return $this->uncompressedSize === 0
            && str_ends_with($this->fileName, '/');
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    public function getRelativeOffsetLocalHeader(): int
    {
        return $this->relativeOffsetLocalHeader;
    }

    public function getDiskNumber(): int
    {
        return $this->diskNumber;
    }
}