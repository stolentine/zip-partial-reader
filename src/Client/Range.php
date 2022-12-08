<?php

namespace Stolentine\ZipPartialReader\Client;

class Range
{
    public function __construct(
        private readonly int $start,
        private readonly int $end,
    ) {
    }

    public static function withLength(int $start, int $length): Range
    {
        return new Range(
            $start,
            $start + $length - 1
        );
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getLength(): int
    {
        return $this->getEnd() - $this->getStart() + 1;
    }

    /**
     * @param int $offset
     * @return void
     */
    public function prevRange(int $offset): Range
    {
        $start = $this->getStart() - $offset - 1;
        if ($start < 0 ) {
            $start = 0;
        }

        return new Range(
            $start,
            $this->getStart() - 1,
        );
    }
}