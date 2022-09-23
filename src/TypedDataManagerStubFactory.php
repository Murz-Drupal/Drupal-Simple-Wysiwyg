<?php

namespace Drupal\test_helpers;

use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub class.
 */
class TypedDataManagerStubFactory extends UnitTestCase {

  /**
   * Creates an entity type stub and defines a static storage for it.
   */
  public function createInstance() {
    /** @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit\Framework\MockObject\MockObject $instance */
    $instance = $this->createPartialMock(TypedDataManager::class, ['getDefinition']);
    UnitTestHelpers::bindClosureToClassMethod(
      function ($plugin_id, $exception_on_invalid = TRUE) {
        // @todo Add support for other plugins.
        if ($plugin_id == 'string') {
          $definition = UnitTestHelpers::getPluginDefinition(StringData::class, 'TypedData');
          return $definition;
        }
        return NULL;
      },
      $instance,
      'getDefinition'
    );

    return $instance;
  }

}
