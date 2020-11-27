<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Test\Unit;

use Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker;
use Lctrs\PsalmPsrContainerPlugin\Plugin;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psalm\Plugin\RegistrationInterface;

final class PluginTest extends TestCase
{
    use ProphecyTrait;

    public function testItRegistersHook(): void
    {
        $api = $this->prophesize(RegistrationInterface::class);
        $api->registerHooksFromClass(PsrContainerChecker::class)->shouldBeCalledOnce();

        (new Plugin())($api->reveal());
    }
}
