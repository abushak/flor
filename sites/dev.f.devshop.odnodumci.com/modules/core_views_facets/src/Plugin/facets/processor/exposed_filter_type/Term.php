<?php

namespace Drupal\core_views_facets\Plugin\facets\processor\exposed_filter_type;

use Drupal\core_views_facets\CoreViewsFacetsFilterType;
use Drupal\facets\FacetInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Filter type "Term" for core_views_facets.
 *
 * @CoreViewsFacetsExposedFilterType(
 *   id = "taxonomy_index_tid",
 *   label = "Taxonomy Terms"
 * )
 */
class Term extends CoreViewsFacetsFilterType {

  /**
   * Locality storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storage;

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
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);

    $this->storage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet) {
    $result = parent::processDatabaseRow($row, $handler, $facet);
    $result->setDisplayValue($this->storage->load($result->getRawValue())->label());

    return $result;
  }

}
