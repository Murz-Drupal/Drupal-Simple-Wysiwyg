<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityFieldManager;

/**
 * A stub of the Drupal's default EntityFieldManager class.
 */
class EntityFieldManagerStub extends EntityFieldManager {

  public function __construct() {
    $this->languageManager = UnitTestHelpers::getServiceStub('language_manager');
  }

  public function stubSetBaseFieldDefinitons($entityTypeId, $baseFieldDefinitions) {
    $this->baseFieldDefinitions[$entityTypeId] = $baseFieldDefinitions;
  }

  public function stubSetFieldDefinitons($entityTypeId, $bundle, $fieldDefinitions) {
    // @todo Get a proper langcode.
    $langcode = 'en';
    $this->baseFieldDefinitions[$entityTypeId][$bundle][$langcode] = $fieldDefinitions;
  }

}
