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
    /**
     * @inheritDoc
     */
    public function __invoke(RegistrationInterface $api, ?SimpleXMLElement $config = null)
    {
        class_exists(PsrContainerChecker::class, true);
        $api->registerHooksFromClass(PsrContainerChecker::class);
    }
}
