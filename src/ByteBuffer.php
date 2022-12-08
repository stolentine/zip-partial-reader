<?php

namespace Stolentine\ZipPartialReader;

class ByteBuffer
{
    public const SHORT_LENGTH = 2;
    public const INT_LENGTH = 4;
    public const LONG_LENGTH = 8;

    private string $bytes = '';
    private int $pointer = 0;

    public static function fromBytes(string $bytes): ByteBuffer
    {
        $instance = new ByteBuffer();

        $instance->bytes = $bytes;
        $instance->pointer = 0;

        return $instance;
    }

    /**
     * get 2 byte int
     * @return int|null
     */
    public function short(): ?int
    {
        return $this->unpuck('v*', $this->nextBytes(static::SHORT_LENGTH));
    }

    /**
     * get 4 byte int
     * @return int|null
     */
    public function int(): ?int
    {
        return $this->unpuck('V*', $this->nextBytes(static::INT_LENGTH));
    }

    /**
     * get 8 byte int
     * @return int|null
     */
    public function long(): ?int
    {
        return $this->unpuck('P*', $this->nextBytes(static::LONG_LENGTH));
    }

    /**
     * get string with length
     * @param int $length
     * @return int
     */
    public function string(int $length): string
    {
        return $this->nextBytes($length);
    }

    /**
     * Check next bytes equals $checkedBytes
     * @param string $checkedBytes
     * @return int
     */
    public function nextStringEquals(string $checkedBytes): bool
    {
        $bytes = $this->nextBytesWithoutShiftPointer(strlen($checkedBytes));

        return $bytes === $checkedBytes;
    }

    /**
     * Check next short equals $checkedShort
     * @param int $checkedShort
     * @return int
     */
    public function nextShortEquals(int $checkedShort): bool
    {
        $short = $this->nextBytesWithoutShiftPointer(2);

        return $this->unpuck('v*', $short) === $checkedShort;
    }

//    /**
//     * Оставшаяся строка
//     * @return string
//     */
//    public function restString()
//    {
//        $length = strlen($this->bytes) - $this->pointer;
//
//        return $this->chunkBytes($length);
//    }

    private function unpuck(string $format, string $bytes): ?int
    {
        return unpack($format, $bytes)[1] ?? null;
    }

    private function nextBytes(int $length): string
    {
        $bytes = $this->nextBytesWithoutShiftPointer($length);
        $this->pointer += $length;

        return $bytes;
    }

    private function nextBytesWithoutShiftPointer(int $length): string
    {
        return substr($this->bytes, $this->pointer, $length);
    }
}