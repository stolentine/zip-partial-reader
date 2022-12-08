# ZipPartialReader

Client for read one file from zip archive by curl.

```php
use Stolentine\ZipPartialReader\ZipPartialReader;

$zip = ZipPartialReader::openUrl('https://example.com/foo.zip');

$path = '/var/www/files/';
$pathName = '/var/www/files/fooBazBar.xml';
$filePathName = 'baz/bar.xml';

foreach ($zip->getFiles() as $file) {
    if (str_starts_with($file->name, $filePathName)) {
        $zip->extractToDir($file, new SplFileInfo($path));
        // or 
        $zip->extractToFile($file, new SplFileInfo($pathName));
    }
}
```