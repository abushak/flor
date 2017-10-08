<?php

namespace Drupal\core_views_facets;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Core views facets filter type plugin manager.
 */
class CoreViewsFacetsExposedFilterTypeManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/processor/exposed_filter_type', $namespaces, $module_handler, 'Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface', 'Drupal\core_views_facets\Annotation\CoreViewsFacetsExposedFilterType');

    $this->setCacheBackend($cache_backend, 'core_views_facets_core_views_facets_exposed_filter_type_plugins');
  }

}
