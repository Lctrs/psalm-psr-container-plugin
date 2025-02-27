<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin;

use Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker;
use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function class_exists;

/** @psalm-api */
final class Plugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, SimpleXMLElement|null $config = null): void
    {
        class_exists(PsrContainerChecker::class, true);
        $registration->registerHooksFromClass(PsrContainerChecker::class);
    }
}
