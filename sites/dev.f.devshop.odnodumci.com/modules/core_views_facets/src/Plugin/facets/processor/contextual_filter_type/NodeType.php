<?php

namespace Drupal\core_views_facets\Plugin\facets\processor\contextual_filter_type;

use Drupal\core_views_facets\CoreViewsFacetsFilterType;
use Drupal\facets\FacetInterface;
use Drupal\node\Entity\NodeType as NodeBundleType;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Filter type "NodeType" for core_views_facets.
 *
 * @CoreViewsFacetsContextualFilterType(
 *   id = "node_type",
 *   label = "Node Bundle Type"
 * )
 */
class NodeType extends CoreViewsFacetsFilterType {

  /**
   * {@inheritdoc}
   */
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet) {
    $result = parent::processDatabaseRow($row, $handler, $facet);
    $result->setDisplayValue(NodeBundleType::load($result->getRawValue())->label());

    return $result;
  }

}
