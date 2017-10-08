<?php

namespace Drupal\core_views_facets;

use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Base class for Core views facets filter type plugins.
 */
abstract class CoreViewsFacetsFilterType extends ProcessorPluginBase implements CoreViewsFacetsFilterTypeInterface {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CoreViewsFacetsFilterType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Alters the facet query before execution.
   *
   * @param \Drupal\views\ViewExecutable $view
   *    The views executable the facet applies to.
   * @param \Drupal\views\Plugin\views\HandlerBase $handler
   *    The loaded views contextual filter handler.
   * @param \Drupal\facets\FacetInterface $facet
   *    The facet being executed.
   *
   * @return null|\Drupal\Core\Database\Query\Select
   *    The altered query object to be executed.
   */
  public function prepareQuery(ViewExecutable &$view, HandlerBase $handler, FacetInterface $facet) {
    $filter_table_alias = [];
    $relationship = NULL;
    if (!empty($handler->options['relationship']) && $handler->options['relationship'] != 'none') {
      /** @var \Drupal\views\Plugin\views\relationship\RelationshipPluginBase $relationship_handler */
      $relationship_handler = $view->getDisplay()->getHandler('relationship', $handler->options['relationship']);
      if ($relationship_handler) {
        $relationship = $relationship_handler->alias;
      }
    }

    /** @var \Drupal\views\Plugin\views\query\Sql $view_query */
    $view_query = $view->query;
    $filter_table_alias[] = $view_query->ensureTable($handler->table, $relationship);

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $view_query->query();

    // The countQuery() method removes everything from the query, that doesn't
    // alter the result count. Such as all the SELECT x,y,z stuff and LEFT joins
    // not in the where clause etc.
    // The countQuery itself though, only counts the whole view, so it's not
    // useful as such, but the subquery can be combined with the facet
    // conditions.
    $query = $query->countQuery();

    // The better alternative $query->prepareCountQuery() is protected, so work
    // around it.
    $query = $query->getTables()['subquery']['table'];

    $select_table_alias = $filter_table_alias[0];
    $select_field = $handler->realField;
    $query->addField($select_table_alias, $select_field, 'facetrawvalue');

    $fields = &$query->getFields();
    $expressions = &$query->getExpressions();

    // Make sure to only group by facetrawvalue.
    $group_by = &$query->getGroupBy();
    foreach ($group_by as $alias => $group_entry) {
      unset($fields[$alias]);
      unset($expressions[$alias]);
    }
    $group_by = [];
    $query->groupBy('facetrawvalue');

    // Remove unnecessary orders.
    $orders = &$query->getOrderBy();
    foreach ($orders as $alias => $order) {
      unset($fields[$alias]);
      unset($expressions[$alias]);
    }
    $orders = [];

    $query->addExpression('COUNT(DISTINCT ' . $view->storage->get('base_table') . '.' . $view->storage->get('base_field') . ')', 'facetcount');

    if (!empty($facet->getHardLimit())) {
      $query->orderBy('facetcount', 'DESC');
      $query->range(0, $facet->getHardLimit());
    }

    // The INNER JOIN should reduce the result set to only the actually
    // available facet values. So we're overriding the default LEFT JOIN.
    foreach ($filter_table_alias as $alias) {
      $tables = &$query->getTables();
      if (!empty($tables[$alias]['join type'])) {
        $tables[$alias]['join type'] = 'INNER';
      }
    }

    return $query;
  }

  /**
   * Alters the result row before displaying the content.
   *
   * @param \stdClass $row
   *    The row as returned by fetchObject().
   * @param \Drupal\views\Plugin\views\HandlerBase $handler
   *    The loaded views contextual filter handler.
   * @param \Drupal\facets\FacetInterface $facet
   *    The facet being executed.
   *
   * @return \Drupal\facets\Result\Result
   *    A valid facet result entity.
   */
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet) {
    $value = $row->facetrawvalue;
    $count = $row->facetcount;
    if (!empty($row->facetlabel)) {
      $label = $row->facetlabel;
    }
    else {
      $label = $value;
    }

    return new Result($value, $label, $count);
  }

}
