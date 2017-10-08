<?php

namespace Drupal\core_views_facets;

use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\FacetInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Defines an interface for Core views facets filter type plugins.
 */
interface CoreViewsFacetsFilterTypeInterface extends ProcessorInterface, ContainerFactoryPluginInterface {

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
  public function prepareQuery(ViewExecutable &$view, HandlerBase $handler, FacetInterface $facet);

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
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet);

}
