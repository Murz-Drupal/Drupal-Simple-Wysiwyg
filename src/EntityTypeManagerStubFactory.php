<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * The EntityTypeManagerStubFactory class.
 */
class EntityTypeManagerStubFactory {

  /**
   * Constructs a new FieldTypeManagerStub.
   */
  /**
   * @var \Drupal\test_helpers\UnitTestHelpers
   */
  private $unitTestHelpers;

  public function __construct() {
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
    UnitTestHelpers::addToContainer('entity.repository', $this->unitTestHelpers->createMock(EntityRepositoryInterface::class));
    UnitTestHelpers::addToContainer('entity_field.manager', (new EntityFieldManagerStubFactory)->createInstance());
    UnitTestHelpers::addToContainer('entity.query.sql', new EntityQueryServiceStub());
    UnitTestHelpers::addToContainer('string_translation', $this->unitTestHelpers->getStringTranslationStub());

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $entityRepository
      ->method('loadEntityByUuid')
      ->willReturnCallback(function ($entityTypeId, $uuid) {
        $entityTypeStorage = \Drupal::service('entity_type.manager')->getStorage($entityTypeId);
        $uuidProperty = $entityTypeStorage->getEntityType()->getKey('uuid');
        return current($entityTypeStorage->loadByProperties([$uuidProperty => $uuid]) ?? []);
      });

    $entityRepository
      ->method('getTranslationFromContext')
      ->will($this->unitTestHelpers->returnArgument(0));
  }

  /**
   * Constructs a new FieldTypeManagerStub.
   */
  public function create(): EntityTypeManagerStubInterface {
    /** @var \Drupal\test_helpers\EntityTypeManagerStubInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManagerStub */
    $entityTypeManagerStub = $this->unitTestHelpers->createPartialMock(EntityTypeManagerStub::class, [
      'findDefinitions',

      // Custom helper functions for the stub:
      // Adds a definition to the static storage.
      'stubAddDefinition',

      // Adds or creates a handler.
      'stubGetOrCreateHandler',

      // Adds or creates a storage.
      'stubGetOrCreateStorage',

      // Initialises the stub object.
      'stubInit',

      // Resets all static storages to empty values.
      'stubReset',
    ]);

    UnitTestHelpers::bindClosureToClassMethod(
      function () {
        return [];
      },
      $entityTypeManagerStub,
      'findDefinitions'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
        if ($forceOverride || !isset($this->definitions[$pluginId])) {
          $this->definitions[$pluginId] = $definition;
        }
        return $this->definitions[$pluginId];
      },
      $entityTypeManagerStub,
      'stubAddDefinition'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
        if ($forceOverride || !isset($this->handlers[$handlerType][$entityTypeId])) {
          $this->handlers[$handlerType][$entityTypeId] = $handler;
        }
        return $this->handlers[$handlerType][$entityTypeId];
      },
      $entityTypeManagerStub,
      'stubGetOrCreateHandler'
    );

    $entityLastInstalledSchemaRepository = $this->unitTestHelpers->createMock(EntityLastInstalledSchemaRepositoryInterface::class);
    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($entityLastInstalledSchemaRepository) {
        $this->container = UnitTestHelpers::getContainerOrCreate();
        $this->entityLastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
      },
      $entityTypeManagerStub,
      'stubInit'
    );
    $entityTypeManagerStub->stubInit();

    UnitTestHelpers::bindClosureToClassMethod(
      function () {
        $this->definitions = [];
      },
      $entityTypeManagerStub,
      'stubReset'
    );

    UnitTestHelpers::addToContainer('entity_type.manager', $entityTypeManagerStub);

    $entityStorageStubFactory = new EntityStorageStubFactory();

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $entityClass, object $storage = NULL, $forceOverride = FALSE) use ($entityStorageStubFactory) {
        $storageNew = $entityStorageStubFactory->createInstance($entityClass);
        $entityTypeId = $storageNew->getEntityTypeId();
        /* @todo Get and register base fields definitions via something like:
         * $entityType = $storageNew->getEntityType();
         * $baseFieldDefinitions = $entityClass::baseFieldDefinitions($entityType);
         * \Drupal::service('entity_field.manager')->stubSetBaseFieldDefinitons($entityTypeId, $baseFieldDefinitions);
         */
        $storage = $this->stubGetOrCreateHandler('storage', $entityTypeId, $storageNew);
        return $storage;
      },
      $entityTypeManagerStub,
      'stubGetOrCreateStorage'
    );

    return $entityTypeManagerStub;
  }

}
