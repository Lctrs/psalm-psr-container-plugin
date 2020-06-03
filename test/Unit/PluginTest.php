<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Test\Unit;

use Lctrs\PsalmPsrContainerPlugin\Plugin;
use PHPUnit\Framework\TestCase;
use Psalm\Plugin\RegistrationInterface;

final class PluginTest extends TestCase
{
    public function testItRegistersHook(): void
    {
        $api = $this->prophesize(RegistrationInterface::class);
        $api->registerHooksFromClass('Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker')->shouldBeCalledOnce();

        (new Plugin())($api->reveal());
    }
}
