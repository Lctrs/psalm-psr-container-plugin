Feature: PsrContainer
  In order to use a PSR Container safely
  As a Psalm user
  I need Psalm to typecheck PSR Containers

  Background:
    Given I have the following config
      """
      <?xml version="1.0"?>
      <psalm totallyTyped="true">
        <projectFiles>
          <directory name="."/>
        </projectFiles>
        <plugins>
          <pluginClass class="Lctrs\PsalmPsrContainerPlugin\Plugin" />
        </plugins>
      </psalm>
      """
    And I have the following code preamble
      """
      <?php

      namespace Foo;

      use Psr\Container\ContainerInterface;

      class Bar
      {
        public const FQCN = self::class;
        public const BAR = 'bar';

        public function bar() : void {}
      }

      """

  Scenario: Asserting psalm recognizes return type of service got via 'ContainerInterface::get()'
    Given I have the following code
      """
      class Foo
      {
        /** @var ContainerInterface */
        private $container;

        public function __construct(ContainerInterface $container)
        {
          $this->container = $container;
        }

        public function variable() : Bar
        {
          $class = Bar::class;

          return $this->container->get($class);
        }

        public function literalString() : Bar
        {
          return $this->container->get('Foo\Bar');
        }

        public function classConstFetch() : Bar
        {
          return $this->container->get(Bar::class);
        }

        public function constFetch() : Bar
        {
          return $this->container->get(Bar::FQCN);
        }

        /**
         * @param class-string<Bar> $class
         */
        public function classString(string $class) : Bar
        {
          return $this->container->get($class);
        }

        /**
         * @template T
         * @param class-string<T> $class
         * @return T
         */
        public function templated(string $class)
        {
          return $this->container->get($class);
        }
      }
      """
    When I run psalm
    Then I see no errors

  Scenario: Asserting psalm recognizes return type of service got via a class implementing ContainerInterface
    Given I have the following code
      """
      class MyContainer implements ContainerInterface
      {
        public function get(string $id)
        {
          return new \stdClass();
        }

        public function has(string $id): bool
        {
          return true;
        }
      }

      class Foo
      {
        /** @var MyContainer */
        private $container;

        public function __construct(MyContainer $container)
        {
          $this->container = $container;
        }

        public function dummy() : Bar
        {
          return $this->container->get(Bar::class);
        }
      }
      """
    When I run psalm
    Then I see no errors
