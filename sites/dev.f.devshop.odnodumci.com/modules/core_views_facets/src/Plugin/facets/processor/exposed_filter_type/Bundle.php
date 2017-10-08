<?php

namespace Drupal\core_views_facets\Plugin\facets\processor\exposed_filter_type;

use Drupal\core_views_facets\CoreViewsFacetsFilterType;
use Drupal\facets\FacetInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Filter type "Bundle" for core_views_facets.
 *
 * @CoreViewsFacetsExposedFilterType(
 *   id = "bundle",
 *   label = "Node Bundle"
 * )
 */
class Bundle extends CoreViewsFacetsFilterType {

  /**
   * {@inheritdoc}
   */
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet) {
    $result = parent::processDatabaseRow($row, $handler, $facet);
    $result->setDisplayValue(NodeType::load($result->getRawValue())->label());

    return $result;
  }

}
