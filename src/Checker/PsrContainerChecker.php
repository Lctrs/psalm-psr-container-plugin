<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Checker;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

use function explode;

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

        $codebase = $event->getCodebase();

        if (
            $className !== ContainerInterface::class
            && ! $codebase->classImplements($className, ContainerInterface::class)
        ) {
            return;
        }

        $arg = $expr->args[0] ?? null;
        if (! $arg instanceof Arg) {
            return;
        }

        $type = $event->getStatementsSource()->getNodeTypeProvider()->getType($arg->value);
        if ($type === null) {
            return;
        }

        $returnTypeCandidates = [];
        foreach ($type->getAtomicTypes() as $atomicType) {
            if ($atomicType instanceof TLiteralClassString) {
                $returnTypeCandidates[] = new TNamedObject($atomicType->value);

                continue;
            }

            if (
                $atomicType instanceof TLiteralString
                && $codebase->classOrInterfaceExists($atomicType->value)
            ) {
                $returnTypeCandidates[] = new TNamedObject($atomicType->value);

                continue;
            }

            if ($atomicType instanceof TTemplateParamClass) {
                $returnTypeCandidates[] = new TTemplateParam(
                    $atomicType->param_name,
                    new Union([$atomicType->as_type ?? new TNamedObject($atomicType->as)]),
                    $atomicType->defining_class
                );

                continue;
            }

            if (! ($atomicType instanceof TClassString)) {
                continue;
            }

            if ($atomicType->as_type === null) {
                continue;
            }

            $returnTypeCandidates[] = $atomicType->as_type;
        }

        if ($returnTypeCandidates === []) {
            return;
        }

        $event->setReturnTypeCandidate(new Union($returnTypeCandidates));
    }
}
