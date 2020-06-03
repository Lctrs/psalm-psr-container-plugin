<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Checker;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

use function explode;

/**
 * @internal
 */
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
    ): void {
        if (! $expr instanceof MethodCall || $return_type_candidate === null) {
            return;
        }

        [$className, $methodName] = explode('::', $declaring_method_id);

        if ($methodName !== 'get') {
            return;
        }

        if (
            $className !== ContainerInterface::class
            && ! $codebase->classImplements($className, ContainerInterface::class)
        ) {
            return;
        }

        $arg = $expr->args[0] ?? null;
        if ($arg === null || ! $arg->value instanceof ClassConstFetch) {
            return;
        }

        $class = $arg->value->class;
        if (! $class->hasAttribute('resolvedName')) {
            return;
        }

        $return_type_candidate = new Union([
            new TNamedObject(
                (string) $class->getAttribute('resolvedName')
            ),
        ]);
    }
}
