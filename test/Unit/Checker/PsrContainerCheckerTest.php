<?php

declare(strict_types=1);

namespace Lctrs\PsalmPsrContainerPlugin\Test\Unit\Checker;

use Lctrs\PsalmPsrContainerPlugin\Checker\PsrContainerChecker;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

final class PsrContainerCheckerTest extends TestCase
{
    use ProphecyTrait;

    private const METHOD_ID = 'Psr\Container\ContainerInterface::get';

    public function testItDoesNothingIfExprIsNotAMethodCall(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            new StaticCall(
                new Variable('dummy'),
                'get',
                [
                    new Arg(
                        new ClassConstFetch(
                            new Name(
                                SomeService::class,
                                ['resolvedName' => new Name('Abracadabra')]
                            ),
                            'class'
                        )
                    ),
                ]
            ),
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

    public function testItDoesNothingIfReturnTypeCandidateIsNull(): void
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
            null
        );

        PsrContainerChecker::afterMethodCallAnalysis($event);

        self::assertNull($event->getReturnTypeCandidate());
    }

    public function testItDoesNothingIfMethodNameIsNotGet(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            'Psr\Container\ContainerInterface::notGet',
            'Psr\Container\ContainerInterface::notGet',
            'Psr\Container\ContainerInterface::notGet',
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

    public function testItDoesNothingIfClassNameIsNotAContainerInterface(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            'stdClass::get',
            'stdClass::get',
            'stdClass::get',
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

    public function testItDoesNothingIfThereIsNoArg(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            new MethodCall(
                new Variable('dummy'),
                'get'
            ),
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

    public function testItDoesNothingIfArgValueIsNotAClassConstFetch(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            new MethodCall(
                new Variable('dummy'),
                'get',
                [
                    new Arg(
                        new String_('dummy')
                    ),
                ]
            ),
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

    public function testItDoesNothingIfItDoesNotHaveAResolvedName(): void
    {
        $event = new AfterMethodCallAnalysisEvent(
            new MethodCall(
                new Variable('dummy'),
                'get',
                [
                    new Arg(
                        new ClassConstFetch(
                            new Name(
                                SomeService::class
                            ),
                            'class'
                        )
                    ),
                ]
            ),
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

    public function testItSetsTheReturnTypeAsAUnionWithFetchedClass(): void
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
        self::assertTrue((new Union([new TNamedObject('Abracadabra')]))->equals($returnTypeCandidate));
    }

    public function testItSetsTheReturnTypeAsAUnionWithFetchedClassWithContainerImplementingContainerInterface(): void
    {
        $codebase = $this->prophesize(Codebase::class);
        $codebase->classImplements(MyContainer::class, ContainerInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $event = new AfterMethodCallAnalysisEvent(
            $this->getMethodCall(),
            MyContainer::class . '::get',
            MyContainer::class . '::get',
            MyContainer::class . '::get',
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $codebase->reveal(),
            [],
            new Union([new TMixed()])
        );

        PsrContainerChecker::afterMethodCallAnalysis($event);

        $returnTypeCandidate = $event->getReturnTypeCandidate();
        self::assertNotNull($returnTypeCandidate);
        self::assertTrue((new Union([new TNamedObject('Abracadabra')]))->equals($returnTypeCandidate));
    }

    private function getMethodCall(): MethodCall
    {
        return new MethodCall(
            new Variable('dummy'),
            'get',
            [
                new Arg(
                    new ClassConstFetch(
                        new Name(
                            SomeService::class,
                            ['resolvedName' => new Name('Abracadabra')]
                        ),
                        'class'
                    )
                ),
            ]
        );
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ClassFileName.NoMatch

final class MyContainer implements ContainerInterface
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

final class SomeService
{
}
