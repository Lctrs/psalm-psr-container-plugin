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
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use Psr\Container\ContainerInterface;

final class PsrContainerCheckerTest extends TestCase
{
    private const METHOD_ID = 'Psr\Container\ContainerInterface::get';

    public function testItDoesNothingIfExprIsNotAMethodCall(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->createStub(StaticCall::class),
            self::METHOD_ID,
            self::METHOD_ID,
            self::METHOD_ID,
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItDoesNothingIfReturnTypeCandidateIsNull(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = null;

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->getMethodCall(),
            self::METHOD_ID,
            self::METHOD_ID,
            self::METHOD_ID,
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertNull($returnTypeCandidate);
    }

    public function testItDoesNothingIfMethodNameIsNotGet(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->getMethodCall(),
            'Psr\Container\ContainerInterface::notGet',
            'Psr\Container\ContainerInterface::notGet',
            'Psr\Container\ContainerInterface::notGet',
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItDoesNothingIfClassNameIsNotAContainerInterface(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->getMethodCall(),
            'stdClass::get',
            'stdClass::get',
            'stdClass::get',
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItDoesNothingIfThereIsNoArg(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
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
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItDoesNothingIfArgValueIsNotAClassConstFetch(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
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
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItDoesNothingIfItDoesNotHaveAResolvedName(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = $baseReturnType = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
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
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertSame($baseReturnType, $returnTypeCandidate);
    }

    public function testItSetsTheReturnTypeAsAUnionWithFetchedClass(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = new Union([new TMixed()]);

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->getMethodCall(),
            self::METHOD_ID,
            self::METHOD_ID,
            self::METHOD_ID,
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $this->createStub(Codebase::class),
            $fileReplacements,
            $returnTypeCandidate
        );

        self::assertNotNull($returnTypeCandidate);
        self::assertTrue((new Union([new TNamedObject('Abracadabra')]))->equals($returnTypeCandidate));
    }

    public function testItSetsTheReturnTypeAsAUnionWithFetchedClassWithContainerImplementingContainerInterface(): void
    {
        $fileReplacements    = [];
        $returnTypeCandidate = new Union([new TMixed()]);

        $codebase = $this->prophesize(Codebase::class);
        $codebase->classImplements(MyContainer::class, ContainerInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        PsrContainerChecker::afterMethodCallAnalysis(
            $this->getMethodCall(),
            MyContainer::class . '::get',
            MyContainer::class . '::get',
            MyContainer::class . '::get',
            $this->createStub(Context::class),
            $this->createStub(StatementsSource::class),
            $codebase->reveal(),
            $fileReplacements,
            $returnTypeCandidate
        );

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
