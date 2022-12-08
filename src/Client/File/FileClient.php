<?php

namespace Stolentine\ZipPartialReader\Client\File;

use Stolentine\ZipPartialReader\Client\Client;
use Stolentine\ZipPartialReader\Client\Range;

class FileClient implements Client
{
    public function __construct(
        private readonly string $filename
    ) {
    }

    public function getBytesByRange(Range $range): string
    {
        $file = fopen($this->filename, "rb");
        fseek($file, $range->getStart());

        return fread($file, $range->getLength());
    }

    public function getFileSize(): int
    {
        return filesize($this->filename);
    }

    public function putBytesByRangeToFile(Range $range)
    {
        $rangeFile = tmpfile();

        $file = fopen($this->filename, "rb");
        fseek($file, $range->getStart());


        $fromByte = $range->getStart();

        do {
            $chunk = 1024;

            $toByte = $fromByte + $chunk;

            if ($toByte > $range->getEnd()) {
                $toByte = $range->getEnd();
            }

            $bytes = fread($file, $toByte - $fromByte);
            fwrite($rangeFile, $bytes);

            $fromByte = $toByte;
        } while ($fromByte < $range->getEnd());


        $filename = stream_get_meta_data($rangeFile)["uri"];
        return fopen($filename, 'rb+');
    }
}