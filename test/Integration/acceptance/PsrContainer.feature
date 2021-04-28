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

  Scenario: Asserting psalm recognizes return type of service got via 'ContainerInterface::get()'
    Given I have the following code
      """
      <?php

      use Psr\Container\ContainerInterface;

      class SomeService
      {
        public function do() : void {}
      }

      class Dummy
      {
        /** @var ContainerInterface */
        private $container;

        public function __construct(ContainerInterface $container)
        {
          $this->container = $container;
        }

        public function dummy() : void
        {
          $this->container->get(SomeService::class)->do();
        }

       /**
        * @param class-string<SomeService> $class
        */
        public function classString(string $class): SomeService {
          return $this->container->get($class);
        }

       /**
        * @template T
        * @param class-string<T> $class
        * @return T
        */
        public function templated(string $class) {
          return $this->container->get($class);
        }

       /**
        * @template T of SomeService
        * @param T|class-string<T> $class
        * @return SomeService
        */
        public function templatedOrStraightforward($class) {
          if ($class instanceof SomeService) {
             return $class;
          }
          return $this->container->get($class);
        }
      }
      """
    When I run psalm
    Then I see no errors

  Scenario: Asserting psalm recognizes return type of service got via a class implementing ContainerInterface
    Given I have the following code
      """
      <?php

      use Psr\Container\ContainerInterface;

      class MyContainer implements ContainerInterface
      {
        public function get($id)
        {
          return 'something';
        }

        public function has($id)
        {
          return true;
        }
      }

      class SomeService
      {
      }

      class Dummy
      {
        /** @var MyContainer */
        private $container;

        public function __construct(MyContainer $container)
        {
          $this->container = $container;
        }

        public function dummy() : void
        {
          $this->container->get(SomeService::class)->unknownMethod();
        }
      }
      """
    When I run psalm
    Then I see these errors
      | Type            | Message                                          |
      | UndefinedMethod | Method SomeService::unknownMethod does not exist |
    And I see no other errors
