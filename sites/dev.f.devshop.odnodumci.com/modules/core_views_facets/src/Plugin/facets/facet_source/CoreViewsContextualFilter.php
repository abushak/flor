<?php

namespace Drupal\core_views_facets\Plugin\facets\facet_source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\core_views_facets\CoreViewsFacetsContextualFilterTypeManager;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\ViewExecutableFactory;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Represents a facet source of the core views with contextual filters.
 *
 * @FacetsFacetSource(
 *   id = "core_views_contextual_filter",
 *   deriver = "Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsContextualFilterDeriver"
 * )
 */
class CoreViewsContextualFilter extends CoreViewsFacetSourceBase {

  /**
   * The filter type plugin manager.
   *
   * @var \Drupal\core_views_facets\CoreViewsFacetsContextualFilterTypeManager
   */
  protected $contextualFilterTypePluginManager;

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
   * @param \Drupal\core_views_facets\CoreViewsFacetsContextualFilterTypeManager $contextual_filter_type_plugin_manager
   *   The filter type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, EntityTypeManagerInterface $entity_type_manager, Request $request, RouteProviderInterface $route_provider, RouteMatchInterface $route_match, ViewExecutableFactory $executable_factory, CoreViewsFacetsContextualFilterTypeManager $contextual_filter_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $entity_type_manager, $request, $route_provider, $route_match, $executable_factory);

    $this->contextualFilterTypePluginManager = $contextual_filter_type_plugin_manager;
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
      $container->get('plugin.manager.core_views_facets.contextual_filter_types')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    if (parent::isRenderedInCurrentRequest()) {
      return TRUE;
    }
    elseif (drupal_static('core_views_contextual_filter_ajax_rendered_status')) {
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

      $views_arguments = $this->getFields();
      reset($views_arguments);
      if (!empty($views_arguments[$facet->getFieldIdentifier()])) {
        $facet_argument = $views_arguments[$facet->getFieldIdentifier()];
      }
      else {
        return;
      }

      $request_arguments = [];
      $map = $this->getViewsArgumentsMap();

      foreach ($map as $current_argument) {
        $request_arguments[] = $current_argument['active'] ? $current_argument['value'] : $current_argument['neutral_value'];
      }

      $this->view->setArguments($request_arguments);
      $this->view->build($this->pluginDefinition['view_display']);

      $facet_core_views_contextual_filter_plugin = $this->loadFacetCoreViewsContextualFilterTypePlugin($facet_argument);
      if (empty($facet_core_views_contextual_filter_plugin)) {
        return;
      }

      $query = $facet_core_views_contextual_filter_plugin->prepareQuery($this->view, $facet_argument, $facet);
      if (empty($query)) {
        return;
      }

      $rows = $query->execute();

      $facet_results = [];
      while ($row = $rows->fetchObject()) {
        $facet_results[] = $facet_core_views_contextual_filter_plugin->processDatabaseRow($row, $facet_argument, $facet);
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

    $arguments = $this->getFields();

    /* @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($arguments as $argument_id => $argument) {
      $form['field_identifier']['#options'][$argument_id] = $argument->adminLabel();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $facet_source_id = $this->facet->getFacetSourceId();
    $views_arguments = $this->getFields();

    $field_identifier = $form_state->getValue('facet_source_configs')[$facet_source_id]['field_identifier'];

    if (empty($field_identifier) || empty($views_arguments[$field_identifier])) {
      return;
    }
    $this->facet->setFieldIdentifier($field_identifier);
  }

  /**
   * Retrieve the argument definition from the current view.
   *
   * @param string|null $argument_id
   *   The string ID of a argument.
   *
   * @return array|bool
   *   An array with the definition of the argument, or FALSE when the plugin
   *   doesn't exist.
   */
  public function getViewsArgumentDefinition($argument_id = NULL) {
    if (empty($argument_id) || empty($this->view)) {
      return FALSE;
    }

    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    $filter = $this->view->getHandler($this->pluginDefinition['view_display'], 'argument', $argument_id);
    return $filter ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $arguments = [];
    foreach ($this->view->getHandlers('argument', $this->view->current_display) as $argument_id => $argument) {
      $arguments[$argument_id] = $this->view->display_handler->getHandler('argument', $argument_id);
    }

    return $arguments;
  }

  /**
   * Load the core_views_facet contextual filter type or fall back to generic.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The views filter handler.
   *
   * @return \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface
   *   The loaded filter type plugin or null.
   */
  protected function loadFacetCoreViewsContextualFilterTypePlugin(ArgumentPluginBase $argument) {
    $filter_type_definitions = $this->contextualFilterTypePluginManager->getDefinitions();

    $custom_filter_id = $argument->view->id() . '-' . $argument->field;
    // Allows to handle custom scenarios.
    if (!empty($filter_type_definitions[$custom_filter_id])) {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->contextualFilterTypePluginManager->createInstance($custom_filter_id);
    }
    // Default filter types for specific filter plugins.
    elseif (!empty($filter_type_definitions[$argument->pluginId])) {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->contextualFilterTypePluginManager->createInstance($argument->pluginId);
    }
    // Generic filter type.
    else {
      /** @var \Drupal\core_views_facets\CoreViewsFacetsFilterTypeInterface $facet_core_views_filter_plugin */
      $facet_core_views_filter_plugin = $this->contextualFilterTypePluginManager->createInstance('generic');
    }

    return $facet_core_views_filter_plugin;
  }

}
