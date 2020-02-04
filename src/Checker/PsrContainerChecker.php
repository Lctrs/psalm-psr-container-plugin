<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Checker;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

final class PsrContainerChecker implements AfterMethodCallAnalysisInterface
{
    /**
     * @inheritDoc
     */
    public static function afterMethodCallAnalysis(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        ?Union &$return_type_candidate = null
    ) : void {
        if ($declaring_method_id !== 'Psr\Container\ContainerInterface::get') {
            return;
        }

        if ($return_type_candidate === null || ! $expr->args[0]->value instanceof ClassConstFetch) {
            return;
        }

        $return_type_candidate = new Union([
            new TNamedObject(
                (string) $expr->args[0]->value->class->getAttribute('resolvedName')
            ),
        ]);
    }
}
