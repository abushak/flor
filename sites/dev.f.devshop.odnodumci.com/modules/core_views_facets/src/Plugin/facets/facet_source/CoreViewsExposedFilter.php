<?php

namespace Drupal\core_views_facets\Plugin\facets\facet_source;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\core_views_facets\CoreViewsFacetsExposedFilterTypeManager;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Represents a facet source of the core views with exposed filters.
 *
 * @FacetsFacetSource(
 *   id = "core_views_exposed_filter",
 *   deriver = "Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsExposedFilterDeriver"
 * )
 */
class CoreViewsExposedFilter extends CoreViewsFacetSourceBase {

  /**
   * The filter type plugin manager.
   *
   * @var \Drupal\core_views_facets\CoreViewsFacetsExposedFilterTypeManager
   */
  protected $exposedFilterTypePluginManager;

  /**
   * Constructs a CoreViewsContextualFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager
   *   The query type plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The master Request.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\core_views_facets\CoreViewsFacetsExposedFilterTypeManager $exposed_filter_type_plugin_manager
   *   The filter type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, EntityTypeManagerInterface $entity_type_manager, Request $request, RouteProviderInterface $route_provider, RouteMatchInterface $route_match, ViewExecutableFactory $executable_factory, CoreViewsFacetsExposedFilterTypeManager $exposed_filter_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $entity_type_manager, $request, $route_provider, $route_match, $executable_factory);

    $this->exposedFilterTypePluginManager = $exposed_filter_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getMasterRequest(),
      $container->get('router.route_provider'),
      $container->get('current_route_match'),
      $container->get('views.executable'),
      $container->get('plugin.manager.core_views_facets.exposed_filter_types')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    if (parent::isRenderedInCurrentRequest()) {
      return TRUE;
    }
    elseif (drupal_static('core_views_exposed_filter_ajax_rendered_status')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults(array $facets) {
    foreach ($facets as $facet_id => $facet) {
      if ($facet->getOnlyVisibleWhenFacetSourceIsVisible()) {
        // Ignore currently unnecessary facets.
        /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
        $facet_source = $facet->getFacetSource();
        if (!$facet_source->isRenderedInCurrentRequest()) {
          continue;
        }
      }

      $request_arguments = [];
      $map = $this->getViewsArgumentsMap();

      foreach ($map as $current_argument) {
        if ($current_argument['active']) {
          $request_arguments[] = $current_argument['value'];
        }
        else {
          break;
        }
      }

      $this->view->setArguments($request_arguments);

      $filters = $this->getFields();

      if (empty($filters[$facet->getFieldIdentifier()])) {
        return;
      }

      $filter = $filters[$facet->getFieldIdentifier()];

      $this->view->build($this->pluginDefinition['view_display']);

      $facet_core_views_exposed_filter_plugin = $this->loadFacetCoreViewsExposedFilterTypePlugin($filter);
      if (empty($facet_core_views_exposed_filter_plugin)) {
        return;
      }

      $query = $facet_core_views_exposed_filter_plugin->prepareQuery($this->view, $filter, $facet);
      if (empty($query)) {
        return;
      }

      try {
        $rows = $query->execute();
      }
      catch (DatabaseExceptionWrapper $e) {
        continue;
      }

      $facet_results = [];
      while ($row = $rows->fetchObject()) {
        $facet_results[] = $facet_core_views_exposed_filter_plugin->processDatabaseRow($row, $filter, $facet);
      }

      $configuration = [
        'query' => NULL,
        'facet' => $facet,
      ];

      $facet->setResults($facet_results);

      // Get the Facet Specific Query Type so we can process the results
      // using the build() function of the query type.
      $query_type = $this->queryTypePluginManager->createInstance($facet->getQueryType(), $configuration);
      $query_type->build();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $filters = $this->getFields();

    /* @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($filters as $filter_id => $filter) {
      if (!$filter->isExposed()) {
        continue;
      }
      $form['field_identifier']['#options'][$filter_id] = $filter->adminLabel();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $facet_source_id = $this->facet->getFacetSourceId();
    $views_filters = $this->getFields();

    $field_identifier = $form_state->getValue('facet_source_configs')[$facet_source_id]['field_identifier'];

    if (empty($field_identifier) || empty($views_filters[$field_identifier])) {
      return;
    }
    $this->facet->setFieldIdentifier($field_identifier);

  }

  /**
   * Retrieve the filter definition from the current view.
   *
   * @param string|null $filter_id
   *   The string ID of a filter.
   *
   * @return \Drupal\views\Plugin\views\filter\FilterPluginBase|bool
   *   An array with the definition of the filter, or FALSE when the plugin
   *   doesn't exist.
   */
  public function getViewsFilterDefinition($filter_id = NULL) {
    if (empty($filter_id) || empty($this->view)) {
      return FALSE;
    }

    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->view->getHandler($this->pluginDefinition['view_display'], 'filter', $filter_id);
    return $filter ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $filters = [];
    foreach ($this->view->getHandlers('filter', $this->view->current_display) as $filter_id => $filter) {
      if (empty($filter['exposed']) || $filter['exposed'] != TRUE) {
        continue;
      }
      $filters[$filter_id] = $this->view->display_handler->getHandler('filter', $filter_id);
    }

    return $filters;
  }

  /**
   * Load the core_views_facet filter type or fall back to generic.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The views filter handler.
   *
   * @return \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface
   *   The loaded filter type plugin or null.
   */
  protected function loadFacetCoreViewsExposedFilterTypePlugin(FilterPluginBase $filter) {
    $filter_type_definitions = $this->exposedFilterTypePluginManager->getDefinitions();

    $custom_filter_id = $filter->view->id() . '-' . $filter->field;
    // Allows to handle custom scenarios.
    if (!empty($filter_type_definitions[$custom_filter_id])) {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->exposedFilterTypePluginManager->createInstance($custom_filter_id);
    }
    // Default filter types for specific filter plugins.
    elseif (!empty($filter_type_definitions[$filter->pluginId])) {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->exposedFilterTypePluginManager->createInstance($filter->pluginId);
    }
    // Generic filter type.
    else {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->exposedFilterTypePluginManager->createInstance('generic');
    }

    return $facet_core_views_filter_plugin;
  }

}
