<?php

namespace Stolentine\ZipPartialReader\Client;

interface Client
{
    public function getBytesByRange(Range $range): string;

    public function getFileSize(): int;

    /**
     * @param Range $range
     * @return resource
     */
    public function putBytesByRangeToFile(Range $range);
}