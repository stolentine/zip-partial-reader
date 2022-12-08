<?php

namespace Stolentine\ZipPartialReader\Client\Curl;

use Stolentine\ZipPartialReader\Client\Client;
use Stolentine\ZipPartialReader\Client\Range;
use Stolentine\ZipPartialReader\Exception\FileException;
use SplFileInfo;
use SplFileObject;
use SplTempFileObject;

class CurlClient implements Client
{
    public function __construct(
        private readonly string $url,
    ) {
    }

    public function getBytesByRange(Range $range): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPGET => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RANGE => sprintf('%d-%d', $range->getStart(), $range->getEnd()),
            CURLOPT_RETURNTRANSFER => true
        ]);

        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

    public function getFileSize(): int
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true,
        ]);

        $content = curl_exec($ch);

        $info = curl_getinfo($ch);
        ksort($info);

        $status = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            throw new FileException("Request status: ".$status);
        }
        curl_close($ch);

        $headers = $this->extractHeadersFromContent($content);

        if (!isset($headers['content-type']) || $headers['content-type'] !== 'application/zip') {
            throw new FileException('File is not a zip archive. Content type: '.$headers['content-type'] ?? '');
        }

        if (!isset($headers['accept-ranges'])) {
            throw new FileException('Server does not support HTTP range requests');
        }

        if (!isset($headers['content-length'])) {
            throw new FileException('Server does not return Content lengths');
        }

        return $headers['content-length'];
    }

    private function extractHeadersFromContent(string $content): array
    {
        $explodeHeadersContent = explode("\n\n", $content, 2);

        $headers = [];
        $rawHeaders = explode("\n", $explodeHeadersContent[0]);

        foreach ($rawHeaders as $rawHeader) {
            $rawHeaderExplode = explode(':', $rawHeader, 2);
            if (count($rawHeaderExplode) < 2) {
                continue;
            }
            [$headerKey, $headerValue] = $rawHeaderExplode;

            $name = str_replace('_', '-', strtolower(trim($headerKey)));
            $value = strtolower(trim($headerValue));
            $headers[$name] = $value;
        }

        return $headers;
    }

    /**
     * @param Range $range
     * @return resource
     */
    public function putBytesByRangeToFile(Range $range)
    {
        $fileResource = tmpfile();
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPGET => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RANGE => sprintf('%d-%d', $range->getStart(), $range->getEnd()),
            CURLOPT_FILE => $fileResource,
            CURLOPT_TIMEOUT => 60 * 25,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);


        curl_exec($ch);
        curl_close($ch);

        $filename = stream_get_meta_data($fileResource)["uri"];

        return fopen($filename, 'rb+');
    }
}