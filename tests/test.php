<?php

namespace Stolentine\ZipPartialReader\Tests;

use Stolentine\ZipPartialReader\ZipPartialReader;

class Test
{
    private string $urlDelta = 'https://fias-file.nalog.ru/downloads/2022.09.09/gar_delta_xml.zip';
    private string $urlFull = 'https://fias-file.nalog.ru/downloads/2022.11.04/gar_xml.zip';

    private string $pathTestTxt = __DIR__ . '/test.txt';
    private string $pathDelta = __DIR__ . '/gar_delta_xml.zip';
    private string $pathArchive = __DIR__ . '/archive.zip';
    private string $pathArchiveComment = __DIR__ . '/archive_comment.zip';
    private string $pathArchiveComment4gb = __DIR__ . '/archive_comment_4gb.zip';
    private string $pathFull = __DIR__ . '/gar_xml.zip';


    public function handle(): void
    {
//        $zip = ZipPartialReader::openFile($this->pathArchiveComment4gb);
//        $zip = ZipPartialReader::openFile($this->pathFull);
//        $zip = ZipPartialReader::openUrl($this->urlFull);
//        $zip = ZipPartialReader::openUrl($this->urlDelta);
        $zip = ZipPartialReader::openFile($this->pathDelta);
//        $zip = ZipPartialReader::openFile($this->pathArchive);
//        $zip = ZipPartialReader::openFile($this->pathArchiveComment);


        $path = storage_path('fias_files');
        $fileName = '61/AS_ADDR_OBJ_20220913_89339b59-c810-4417-b032-9f4aa9b380fd.XML';
        foreach ($zip->getFiles() as $file) {

            if (str_starts_with($file->name, $fileName)) { //'61/'
                $zip->extractToDir($file, new SplFileInfo($path));
            }

        }
    }
}

(new Test)->handle();