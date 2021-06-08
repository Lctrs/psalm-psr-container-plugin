<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Test\Unit\Checker;

use Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

class PsrContainerCheckerClassStringTest extends TestCase
{
    use ProphecyTrait;

    private const METHOD_ID     = 'Psr\Container\ContainerInterface::get';
    private const VARIABLE_NAME = 'param';

    public function testItDoesNothingWithEmptyContext(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            self::METHOD_ID,
            self::METHOD_ID,
            self::METHOD_ID,
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            [],
            new Union([new TMixed()])
        );

        PsrContainerChecker::afterMethodCallAnalysis($event);

        $returnTypeCandidate = $event->getReturnTypeCandidate();
        self::assertNotNull($returnTypeCandidate);
        self::assertTrue($returnTypeCandidate->equals(new Union([new TMixed()])));
    }

    /**
     * @dataProvider pairsProvider
     */
    public function testItSetsTheReturnTypeAsAUnionWithFetchedClass(Union $variableType, Union $expectedType): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            self::METHOD_ID,
            self::METHOD_ID,
            self::METHOD_ID,
            $this->createContext($variableType),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            [],
            new Union([new TMixed()])
        );

        PsrContainerChecker::afterMethodCallAnalysis($event);

        $returnTypeCandidate = $event->getReturnTypeCandidate();
        self::assertNotNull($returnTypeCandidate);
        self::assertTrue($expectedType->equals($returnTypeCandidate));

        if (! $expectedType->hasTemplate()) {
            return;
        }

        // we should also check for template match
        self::assertEquals($expectedType->getId(), $returnTypeCandidate->getId());
    }

    /**
     * @dataProvider pairsProvider
     */
    public function testItSetsTheReturnTypeAsAUnionWithFetchedClassWithContainerImplementingContainerInterface(
        Union $variableType,
        Union $expectedType
    ): void {
        $codebase = $this->prophesize(Codebase::class);
        $codebase->classImplements(MyOtherContainer::class, ContainerInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            MyOtherContainer::class . '::get',
            MyOtherContainer::class . '::get',
            MyOtherContainer::class . '::get',
            $this->createContext($variableType),
            $this->createStub(StatementsSource::class),
            $codebase->reveal(),
            [],
            new Union([new TMixed()])
        );

        PsrContainerChecker::afterMethodCallAnalysis($event);

        $returnTypeCandidate = $event->getReturnTypeCandidate();
        self::assertNotNull($returnTypeCandidate);
        self::assertTrue($expectedType->equals($returnTypeCandidate));

        if (! $expectedType->hasTemplate()) {
            return;
        }

        // we should also check for template match
        self::assertEquals($expectedType->getId(), $returnTypeCandidate->getId());
    }

    /**
     * @return array<string, array{0: Union, 1: Union}>
     */
    public function pairsProvider(): array
    {
        return [
            'mixed variable' => [
                new Union([new TMixed()]),
                new Union([new TMixed()]),
            ],
            'class string' => [
                new Union([
                    new TClassString('object', new TNamedObject('Abracadabra')),
                ]),
                new Union([new TNamedObject('Abracadabra')]),
            ],
            'class string and mixed' => [
                new Union([
                    new TClassString('object', new TNamedObject('Abracadabra')),
                    new TMixed(),
                ]),
                new Union([
                    new TNamedObject('Abracadabra'),
                    new TMixed(),
                ]),
            ],
            'templated class string without as_type' => [
                new Union([
                    new TTemplateParamClass(
                        'T',
                        'object',
                        null,
                        'definingclass'
                    ),
                ]),
                new Union([
                    new TTemplateParam(
                        'T',
                        new Union([new TObject()]),
                        'definingclass'
                    ),
                ]),
            ],
            'templated class string with as_type' => [
                new Union([
                    new TTemplateParamClass(
                        'T',
                        'Abracadabra',
                        new TNamedObject('Abracadabra'),
                        'definingclass'
                    ),
                ]),
                new Union([
                    new TTemplateParam(
                        'T',
                        new Union([new TNamedObject('Abracadabra')]),
                        'definingclass'
                    ),
                ]),
            ],
            'union of class string or templated class string' => [
                new Union([
                    new TTemplateParamClass(
                        'T',
                        'object',
                        null,
                        'definingclass'
                    ),
                    new TClassString('object', new TNamedObject('Abracadabra')),
                ]),
                new Union([
                    new TTemplateParam(
                        'T',
                        new Union([new TObject()]),
                        'definingclass'
                    ),
                    new TNamedObject('Abracadabra'),
                ]),
            ],
        ];
    }

    /**
     * @return Stub&Context
     */
    protected function createContext(Union $variableType)
    {
        $stub = $this->createStub(Context::class);

        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $stub->vars_in_scope['$' . self::VARIABLE_NAME] = $variableType;

        return $stub;
    }

    protected function getMethodCall(): MethodCall
    {
        return new MethodCall(
            new Variable('dummy'),
            'get',
            [
                new Arg(
                    new Variable(self::VARIABLE_NAME)
                ),
            ]
        );
    }
}


// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ClassFileName.NoMatch

final class MyOtherContainer implements ContainerInterface
{
    /**
     * @inheritDoc
     */
    public function get($id)
    {
        return 'dummy';
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return true;
    }
}
