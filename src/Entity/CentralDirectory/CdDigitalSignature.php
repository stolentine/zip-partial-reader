<?php

namespace Stolentine\ZipPartialReader\Entity\CentralDirectory;

use Stolentine\ZipPartialReader\ByteBuffer;
use Stolentine\ZipPartialReader\Entity\SignatureBytes;
use Stolentine\ZipPartialReader\Exception\ZipPartialReaderException;

class CdDigitalSignature
{
   /* todo

   4.3.13 Digital signature:

        header signature                4 bytes  (0x05054b50)
        size of data                    2 bytes
        signature data (variable size)

      With the introduction of the Central Directory Encryption
      feature in version 6.2 of this specification, the Central
      Directory Structure MAY be stored both compressed and encrypted.
      Although not required, it is assumed when encrypting the
      Central Directory Structure, that it will be compressed
      for greater storage efficiency.  Information on the
      Central Directory Encryption feature can be found in the section
      describing the Strong Encryption Specification. The Digital
      SignatureBytes record will be neither compressed nor encrypted.


    */
}