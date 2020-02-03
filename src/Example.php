<?php

declare(strict_types=1);

namespace Lctrs\Library;

final class Example
{
    /** @var string */
    private $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function fromName(string $name) : self
    {
        return new self($name);
    }

    public function name() : string
    {
        return $this->name;
    }
}
