<?php

declare(strict_types=1);

namespace Lctrs\Library\Test\Unit;

use Ergebnis\Test\Util\Helper;
use Lctrs\Library\Example;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Lctrs\Library\Example
 */
final class ExampleTest extends TestCase
{
    use Helper;

    public function testFromNameReturnsExample() : void
    {
        $name = self::faker()->sentence;

        $example = Example::fromName($name);

        self::assertSame($name, $example->name());
    }
}
