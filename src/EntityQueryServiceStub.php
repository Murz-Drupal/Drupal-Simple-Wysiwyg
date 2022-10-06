<?php

namespace Drupal\test_helpers;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * The EntityQueryServiceStub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
class EntityQueryServiceStub implements QueryFactoryInterface {

  /**
   * Constructs a new EntityQueryServiceStub.
   */
  public function __construct() {
    $this->queryStubFactory = new EntityQueryStubFactory();
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->namespaces[] = 'Drupal\Core\Entity\Query\Sql';
  }

  /**
   * Gets the query for entity type.
   */
  public function get($entityType, $conjunction) {
    $executeFunction =
      $this->executeFunctions[$entityType->id()]
      ?? $this->executeFunctions['all']
      ?? function () {
        return [];
      };
    $query = $this->queryStubFactory->get($entityType, $conjunction, $executeFunction);
    return $query;
  }

  /**
   * Gets the aggregate query for entity type.
   */
  public function getAggregate($entityType, $conjunction) {
    // @todo Implement a custom getAggregate call.
    return $this->get($entityType, $conjunction);
  }

  /**
   * Adds an execute callback function to the particular entity type.
   */
  public function stubAddExecuteHandler(callable $function, string $entityTypeId = 'all') {
    $this->executeFunctions[$entityTypeId] = $function;
  }

  public function stubCheckConditionsMatch(ConditionInterface $conditionsExpected, $onlyListed = FALSE) {
    return UnitTestHelpers::matchConditions($conditionsExpected, $this->condition, $onlyListed);
  }

}
