<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Checker;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

use function explode;
use function is_string;

/**
 * @internal
 */
final class PsrContainerChecker implements AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        $expr = $event->getExpr();

        if (! $expr instanceof MethodCall || $event->getReturnTypeCandidate() === null) {
            return;
        }

        [$className, $methodName] = explode('::', $event->getDeclaringMethodId());

        if ($methodName !== 'get') {
            return;
        }

        if (
            $className !== ContainerInterface::class
            && ! $event->getCodebase()->classImplements($className, ContainerInterface::class)
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

            $variableType = $event->getContext()->vars_in_scope['$' . $arg->value->name] ?? null;
            if (! $variableType instanceof Union) {
                return;
            }

            $candidate = self::handleVariable($variableType);
            if (! $candidate->isMixed()) {
                $event->setReturnTypeCandidate($candidate);
            }

            return;
        }

        $class = $arg->value->class;
        if (! $class->hasAttribute('resolvedName')) {
            return;
        }

        $event->setReturnTypeCandidate(new Union([
            new TNamedObject(
                (string) $class->getAttribute('resolvedName')
            ),
        ]));
    }

    private static function handleVariable(Union $variableType): Union
    {
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
                $types[] = new Atomic\TMixed();
            }
        }

        return new Union($types);
    }
}
