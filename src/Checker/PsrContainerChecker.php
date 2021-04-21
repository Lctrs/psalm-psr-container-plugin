<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Checker;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

use function count;
use function explode;
use function is_string;

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
        if ($arg === null) {
            return;
        }

        if (! $arg->value instanceof ClassConstFetch) {
            if (! $arg->value instanceof Variable || ! is_string($arg->value->name)) {
                return;
            }

            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $variableType = $context->vars_in_scope['$' . $arg->value->name] ?? null;
            if (! $variableType instanceof Union) {
                return;
            }

            $candidate = self::handleVariable($variableType);
            if ($candidate !== null) {
                $return_type_candidate = $candidate;
            }

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

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    protected static function handleVariable(Union $variableType): ?Union
    {
        $hasMixed = false;
        /** @var list<Atomic> $types */
        $types = [];
        foreach ($variableType->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParamClass) {
                $types[] = new Atomic\TTemplateParam(
                    $type->param_name,
                    new Union([$type->as_type ?? new TNamedObject($type->as)]),
                    $type->defining_class
                );
            } elseif ($type instanceof TClassString && $type->as_type !== null) {
                $types[] = $type->as_type;
            } else {
                if (! $hasMixed) {
                    $types[] = new Atomic\TMixed();
                }

                $hasMixed = true;
            }
        }

        if (count($types) > 0) {
            return new Union($types);
        }

        return null;
    }
}
