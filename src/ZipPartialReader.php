<?php

namespace Stolentine\ZipPartialReader;

use Stolentine\ZipPartialReader\Client\Client;
use Stolentine\ZipPartialReader\Client\Curl\CurlClient;
use Stolentine\ZipPartialReader\Client\File\FileClient;
use Stolentine\ZipPartialReader\Client\Range;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\CdFileDescriptor;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\CdFileHeader;
use Stolentine\ZipPartialReader\Entity\CentralDirectory\StaticCdFileHeader;
use Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory\Eocd;
use Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory\Zip64EocdLocator;
use Stolentine\ZipPartialReader\Entity\EndOfCentralDirectory\Zip64EocdRecord;
use Stolentine\ZipPartialReader\Entity\File;
use Stolentine\ZipPartialReader\Entity\LocalFile\StaticLocalFileHeader;
use Stolentine\ZipPartialReader\Exception\SignatureNotFoundException;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;
use Generator;
use SplFileInfo;

final class ZipPartialReader
{
    private File $file;
    private Eocd $eocd;
    private ?Zip64EocdLocator $zip64EocdLocator = null;
    private ?Zip64EocdRecord $zip64EocdRecord = null;

    /** @var array<CdFileHeader>  */
    private array $cdFileHeaders = [];

    public static function openUrl(string $url)
    {
        return new ZipPartialReader(
            new CurlClient($url)
        );
    }

    public static function openFile(string $filename)
    {
        return new ZipPartialReader(
            new FileClient($filename)
        );
    }

    protected function __construct(
        private readonly Client $client,
    ) {
        $this->file = new File(
            $this->client->getFileSize()
        );

        $this->eocd = $this->findEocd();

        if (!$this->file->hasStandardSize()) {
            $this->zip64EocdLocator = $this->findZip64EocdLocator();
            $this->zip64EocdRecord = $this->findZip64EocdReocord();
        }

        // todo Another function

        //todo move to EocdManger
        $isZip64 = $this->zip64EocdRecord !== null;
        if ($isZip64) {
            $range = Range::withLength(
                $this->zip64EocdRecord->getOffsetCd(),
                $this->zip64EocdRecord->getSizeCd(),
            );
        } else {
            $range = Range::withLength(
                $this->eocd->getOffsetCd(),
                $this->eocd->getSizeCd(),
            );
        }

        // todo записывать в файл и выдавать итератор
        $bytes = $this->client->getBytesByRange($range);
        $byteBuffer = ByteBuffer::fromBytes($bytes);

        $cdFileHeaderSignatureBytes = StaticCdFileHeader::getSignatureBytes()->toBytes();
        while ($byteBuffer->nextStringEquals($cdFileHeaderSignatureBytes)) {
            $this->cdFileHeaders[] = StaticCdFileHeader::fromBytes($byteBuffer);
        }
    }

    /**
     * todo нужно подумать над именами ибо getFiles и getFile возвращают разные объекты
     * Возвращает список дескрипторов файлов взятых из CdFileHeader
     *
     * @return Generator<CdFileDescriptor>
     */
    public function getFiles(): Generator
    {
        foreach ($this->cdFileHeaders as $index => $cdFileHeader) {
            yield new CdFileDescriptor(
                $index,
                $cdFileHeader->getFileName(),
                $cdFileHeader->getUncompressedSize(),
            );
        }
    }

    public function extractToByIndex(int $index, SplFileInfo $file): void
    {
        $cdFileHeaders = $this->getCdFileHeaderByIndex($index);

        $headerRange = Range::withLength(
            $cdFileHeaders->getRelativeOffsetLocalHeader(),
            StaticLocalFileHeader::getHeaderLength(),
        );

        $headerBytes = $this->client->getBytesByRange($headerRange);
        $headerByteBuffer = ByteBuffer::fromBytes($headerBytes);

        $localFile = StaticLocalFileHeader::fromBytes($headerByteBuffer);

        ///// load variable fields

        $variableFieldsRange = Range::withLength(
            $cdFileHeaders->getRelativeOffsetLocalHeader() + StaticLocalFileHeader::getHeaderLength(),
            $localFile->getFullVariableLength(),
        );
        $variableFieldsBytes = $this->client->getBytesByRange($variableFieldsRange);
        $variableFieldsByteBuffer = ByteBuffer::fromBytes($variableFieldsBytes);

        $localFile = $localFile->fromBytesWithVariableFields($variableFieldsByteBuffer);

        ///// load file data

        $range = Range::withLength(
            $cdFileHeaders->getRelativeOffsetLocalHeader() + StaticLocalFileHeader::getHeaderLength() + $localFile->getFullVariableLength(),
            $localFile->getCompressedSize(),
        );

        $compressedFile = $this->client->putBytesByRangeToFile($range);

        $localFile->uncompresse($compressedFile, $file);
    }

    public function extractToDir(CdFileDescriptor $descriptor, SplFileInfo $dir): void
    {
        $this->extractToFile($descriptor, new SplFileInfo($dir->getPathname().'/'.$descriptor->name));
    }

    public function extractToFile(CdFileDescriptor $descriptor, SplFileInfo $file): void
    {
        $this->extractToByIndex($descriptor->index, $file);
    }

    private function getCdFileHeaderByIndex(int $index): CdFileHeader
    {
        return $this->cdFileHeaders[$index];
    }

    /**
     * @throws ZipPartialReaderException
     */
    private function findEocd(): Eocd
    {
        $range = Range::withLength(
            $this->file->getSize() - Eocd::getHeaderLength(),
            Eocd::getHeaderLength(),
        );

        $bytes = $this->client->getBytesByRange($range);

        if (!Eocd::getSignatureBytes()->startsWithSignature($bytes)) {
            $bytes = $this->findEOCDWithComment($bytes, $range);
        }

        return Eocd::fromBytes(ByteBuffer::fromBytes($bytes));
    }

    /**
     * Ищет список байтов конца центральной директории вместе с комментарием.
     * @param string $bytes
     * @param mixed $range
     * @return false|string
     * @throws ZipPartialReaderException
     */
    private function findEOCDWithComment(string $bytes, mixed $range): string|false
    {
        //it's magic number
        $SEARCH_SIGNATURE_LENGTH = 256;

        while (true) {
            $range = $range->prevRange($SEARCH_SIGNATURE_LENGTH);
            $bytes = $this->client->getBytesByRange($range) . $bytes;

            try {
                return Eocd::getSignatureBytes()->getBytesAfterSignature($bytes);
            } catch (SignatureNotFoundException) {
            }

            if ($range->getStart() === 0) {
                throw new ZipPartialReaderException('Invalid file. Expected ZIP archive');
            }
        }
    }
    private function findZip64EocdLocator(): Zip64EocdLocator
    {
        $range = Range::withLength(
            $this->file->getSize() - ($this->eocd->getTotalLength() + Zip64EocdLocator::getHeaderLength()),
            Zip64EocdLocator::getHeaderLength(),
        );

        $bytes = $this->client->getBytesByRange($range);

        return Zip64EocdLocator::fromBytes(ByteBuffer::fromBytes($bytes));
    }

    private function findZip64EocdReocord(): Zip64EocdRecord
    {
        $range = new Range(
            $this->zip64EocdLocator->getOffsetZip64EocdRecord(),
            $this->file->getSize() - ($this->eocd->getTotalLength() + Zip64EocdLocator::getHeaderLength()) - 1
        );

        $bytes = $this->client->getBytesByRange($range);

        return Zip64EocdRecord::fromBytes(ByteBuffer::fromBytes($bytes));
    }
}
