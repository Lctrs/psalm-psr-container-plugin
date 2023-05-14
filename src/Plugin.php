<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin;

use Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function class_exists;

final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, SimpleXMLElement|null $config = null): void
    {
        class_exists(PsrContainerChecker::class, true);
        $registration->registerHooksFromClass(PsrContainerChecker::class);
    }
}
