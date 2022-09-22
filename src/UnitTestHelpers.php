<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Helpers for TVH Unit tests.
 */
class UnitTestHelpers extends UnitTestCase {

  /**
   * Gets an accessible method from class using reflection.
   */
  public static function getAccessibleMethod(\stdclass $className, string $methodName): \ReflectionMethod {
    $class = new \ReflectionClass($className);
    $method = $class
      ->getMethod($methodName);
    $method
      ->setAccessible(TRUE);
    return $method;
  }

  /**
   * Parses the annotation for a class and gets the definition.
   */
  public static function getPluginDefinition(string $class, string $plugin, string $annotationName = NULL): PluginDefinition|array {
    static $definitions;

    if (isset($definitions[$plugin][$class])) {
      return $definitions[$plugin][$class];
    }

    $rc = new \ReflectionClass($class);

    $reader = new SimpleAnnotationReader();
    $reader->addNamespace('Drupal\Core\Annotation');
    $reader->addNamespace('Drupal\Core\\' . $plugin . '\Annotation');

    // If no annotation name is passed, just getting the first anotatin;
    if (!$annotationName) {
      $annotation = current($reader->getClassAnnotations($rc));
    }
    else {
      $annotation = $reader->getClassAnnotation($rc, $annotationName);
    }
    if ($annotation) {
      // Inline copy of the proteced function
      // AnnotatedClassDiscovery::prepareAnnotationDefinition().
      $annotation->setClass($class);

      $definition = $annotation->get();

      return $definition;
    }
  }

  /**
   * Adds a new service to the Drupal container, if exists - reuse existing.
   */
  public static function addToContainer(string $serviceName, object $class, bool $override = FALSE): ?object {
    $container = \Drupal::hasContainer()
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    $currentService = $container->has($serviceName)
      ? $container->get($serviceName)
      : new \stdClass();
    if (
      (get_class($currentService) !== get_class($class))
      || $override
    ) {
      $container->set($serviceName, $class);
    }
    \Drupal::setContainer($container);

    return $container->get($serviceName);
  }

  /**
   * Gets the service from the Drupal container, or creates a new one.
   */
  public static function getFromContainerOrCreate(string $serviceName, object $class): ?object {
    $container = \Drupal::hasContainer()
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    if (!$container->has($serviceName)) {
      $container->set($serviceName, $class);
      \Drupal::setContainer($container);
    }
    return $container->get($serviceName);
  }

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   */
  public function createPartialMockWithCostructor(string $originalClassName, array $methods, array $constructorArgs): MockObject {
    return $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs)
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->onlyMethods(empty($methods) ? NULL : $methods)
      // ->enableProxyingToOriginalMethods()
      ->getMock();
  }

  /**
   * Binds a closure function to a mocked class method.
   */
  public static function bindClosureToClassMethod(\Closure $closure, MockObject $class, string $method): void {
    $doClosure = $closure->bindTo($class, get_class($class));
    $class->method($method)->willReturnCallback($doClosure);
  }

}
