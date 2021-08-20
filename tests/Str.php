<?php

namespace Raigu\OrderedDataSynchronization;

require_once __DIR__ . '/Stringable.php';



final class Str implements \Stringable
{
    private string $value;

    public function __toString(): string
    {
        return $this->value;
    }

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}