<?php

namespace Stolentine\ZipPartialReader\Entity\LocalFile;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\LocalFile\ExtraField\Zip64ExtendedInformation;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;
use SplFileInfo;

class StaticLocalFileHeader implements LocalFileHeader
{
    private const SIGNATURE_BYTES = "\x50\x4b\x03\x04"; //PK
    private const HEADER_LENGTH = 30; // bytes without variable fields

    /**
     * local file header signature
     *
     * 4 bytes  (0x04034b50)
     */
    private readonly int $signature;

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
     * extra field  length
     *
     * 2 bytes
     */
    private readonly int $extraFieldLength;

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

    // 4.5.2
    private static array $extraFieldTypes = [
        Zip64ExtendedInformation::class,
//        OS2::class,
    ];

    public static function fromBytes(ByteBuffer $bytes)
    {
        if (!$bytes->nextStringEquals(static::SIGNATURE_BYTES)) {
            throw new ZipPartialReaderException("Invalid bytes string. Must be start vis signature: " . static::SIGNATURE_BYTES);
        }

        $instance = new static();
        $instance->signature = $bytes->int();
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

        return $instance;
    }


    public static function getHeaderLength(): int
    {
        return static::HEADER_LENGTH;
    }

    public function getFullVariableLength(): int
    {
        return $this->fileNameLength + $this->extraFieldLength;
    }

    public function fromBytesWithVariableFields(ByteBuffer $bytes): LocalFileHeader
    {
        $instance = $this;

        $instance->fileName = $bytes->string($instance->fileNameLength);
        $instance->extraFieldBytes = $bytes->string($instance->extraFieldLength);

        // todo инкапсулировать
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


    public function uncompresse($compressedFile, SplFileInfo $file): void
    {
        if ($this->compressionMethod === 8) {
            stream_filter_append($compressedFile, "zlib.inflate", STREAM_FILTER_READ);

            $concurrentDirectory = $file->getPath();
            if (!is_dir($file->getPath())) {
                if (!mkdir($concurrentDirectory, recursive: true) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }
            $file = new SplFileInfo($file->getPathname());

            $uncompressedFile = $file->openFile('wb+');

            while (!feof($compressedFile)) {
                $partContent = fread($compressedFile, 1024);

                $uncompressedFile->fwrite($partContent);
            }

            return;
        }

        throw new \LogicException('method not implemented');
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
}