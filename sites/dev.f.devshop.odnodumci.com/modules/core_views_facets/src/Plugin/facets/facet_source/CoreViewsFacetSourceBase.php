<?php

namespace Drupal\core_views_facets\Plugin\facets\facet_source;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginBase;
use Drupal\views\ViewExecutableFactory;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provide common functions for core Views based facet sources.
 */
abstract class CoreViewsFacetSourceBase extends FacetSourcePluginBase {

  /**
   * The current view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The url processor name.
   *
   * @var string
   */
  protected $urlProcessor = 'core_views_url_processor';

  /**
   * The master request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, EntityTypeManagerInterface $entity_type_manager, Request $request, RouteProviderInterface $route_provider, RouteMatchInterface $route_match, ViewExecutableFactory $executable_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager);

    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration;
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request;
    $this->routeProvider = $route_provider;
    $this->routeMatch = $route_match;
    // Load the view.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->entityTypeManager->getStorage('view')
      ->load($plugin_definition['view_id']);
    $this->view = $executable_factory->get($view);
    $this->view->setDisplay($plugin_definition['view_display']);
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
      $container->get('views.executable')
    );
  }

  /**
   * Return the current view.
   *
   * @return \Drupal\views\ViewExecutable
   *   Current view.
   */
  public function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $path = $this->view->getPath();
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    if ($this->request->attributes->get('_controller') === 'Drupal\views\Routing\ViewPageController::handle') {
      list(, $view) = explode(':', $this->getPluginId());
      list($view_id, $view_display) = explode('__', $view);

      if ($this->request->attributes->get('view_id') == $view_id && $this->request->attributes->get('display_id') == $view_display) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['field_identifier'] = [
      '#type' => 'select',
      '#options' => [],
      '#title' => $this->t('Facet field'),
      '#description' => $this->t('Choose the filter to facet by.'),
      '#required' => TRUE,
      '#default_value' => $this->facet->getFieldIdentifier(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $facet_source_id = $this->facet->getFacetSourceId();
    $triggering_element = $form_state->getTriggeringElement();

    if (!empty($triggering_element['#name']) && $triggering_element['#name'] == 'facet_source_configure') {
      return;
    }

    $views_filters = $this->getFields();
    $field_identifier = $form_state->getValue('facet_source_configs')[$facet_source_id]['field_identifier'];

    if (empty($field_identifier) || empty($views_filters[$field_identifier])) {
      $form_state->setErrorByName('facet_source_id', t('Please select a valid field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    // We don't actually use this. Just put something that exists.
    return [
      'string' => 'search_api_string',
    ];
  }

  /**
   * Retrieves the current views arguments map and returns a detailed version.
   *
   * @return array
   *   Detail enriched argument data.
   */
  public function getViewsArgumentsMap() {
    if (empty($this->view->getDisplay()->getRoutedDisplay())) {
      return [];
    }

    $views_arguments = [];
    $arguments = $this->view->getHandlers('argument', $this->view->current_display);
    foreach ($arguments as $argument_id => $argument) {
      $views_arguments[$argument_id] = $this->view->display_handler->getHandler('argument', $argument_id);
    }
    reset($views_arguments);

    $route_name = $this->view->getUrl()->getRouteName();
    try {
      $route = $this->routeProvider->getRouteByName($route_name);
    }
    catch (RouteNotFoundException $e) {
      return [];
    }
    $route_map = $route->hasOption('_view_argument_map') ? $route->getOption('_view_argument_map') : [];

    // Where do arguments come from?
    if ($this->isRenderedInCurrentRequest()) {
      if ($ajax_arguments = drupal_static('core_views_contextual_filter_ajax_arguments')) {
        $argument_source = 'view_ajax';
        reset($ajax_arguments);
      }
      else {
        $argument_source = 'view_page';
      }
    }
    else {
      $argument_source = 'view_defaults';
    }

    // Each argument can only be active, if each previous argument was active
    // too. This variable is set to FALSE for all following arguments, when any
    // one argument is inactive.
    $active_value = TRUE;

    $map = [];
    // For all I can tell, the association view argument <-> route argument
    // happens entirely by counting up in both arrays...
    foreach ($route_map as $attribute => $parameter_name) {
      $current_map_entry = [];

      // Allow parameters be pulled from the request.
      // The map stores the actual name of the parameter in the request. Views
      // which override existing controller, use for example 'node' instead of
      // arg_nid as name.
      if (isset($map[$attribute])) {
        $attribute = $map[$attribute];
      }

      $current_map_entry['route_parameter'] = $attribute;
      $current_map_entry['views_argument_id'] = key($views_arguments);
      $current_map_entry['views_argument'] = current($views_arguments);
      next($views_arguments);

      $current_map_entry['neutral_value'] = empty($current_map_entry['views_argument']->options['exception']['value']) ? 'all' : $current_map_entry['views_argument']->options['exception']['value'];

      switch ($argument_source) {
        case 'view_page':
          // This is views page request, standard procedure.
          if ($current_request_argument = $this->routeMatch->getRawParameter($attribute)) {
          }
          else {
            $current_request_argument = $this->routeMatch->getParameter($attribute);
          }

          if (isset($current_request_argument)) {
            $current_map_entry['value'] = $current_request_argument;
            $current_map_entry['active'] = $active_value;
          }
          else {
            $current_map_entry['value'] = $current_map_entry['neutral_value'];
            $current_map_entry['active'] = FALSE;
            $active_value = FALSE;
          }
          break;

        case 'view_ajax':
          if (current($ajax_arguments)) {
            $current_map_entry['value'] = current($ajax_arguments);
            $current_map_entry['active'] = $active_value;
            if ($current_map_entry['value'] == $current_map_entry['neutral_value']) {
              $current_map_entry['active'] = FALSE;
            }
            next($ajax_arguments);
          }
          else {
            $current_map_entry['value'] = $current_map_entry['neutral_value'];
            $current_map_entry['active'] = FALSE;
            $active_value = FALSE;
          }
          break;

        default:
          // Just add the defaults.
          $current_map_entry['value'] = $current_map_entry['neutral_value'];
          $current_map_entry['active'] = FALSE;
          $active_value = FALSE;
      }

      $map[$current_map_entry['views_argument_id']] = $current_map_entry;
    }

    return $map;
  }

  /**
   * Optionally get JS settings when AJAX is enabled.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to get settings for.
   *
   * @return array
   *   The drupalSettings array.
   */
  public function getAjaxSettingsByFacet(FacetInterface $facet) {
    /** @var \Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsFacetSourceBase $facet_source */
    $facet_source = $facet->getFacetSource();

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $facet_source->getView();

    $filter_handler = $view->getHandler($view->current_display, 'filter', $facet->getFieldIdentifier());

    return [
      'view_id' => $view->id(),
      'current_display_id' => $view->current_display,
      'field_id' => $filter_handler['expose']['identifier'],
      'type' => $facet_source->getBaseId(),
      'view_base_path' => ltrim($view->getPath(), '/'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $plugin_id_array = explode(':', $this->pluginId);
    list($view_id,) = explode('__', $plugin_id_array[1]);
    $dependencies['config'] = ['views.view.' . $view_id];
    $dependencies['module'] = ['core_views_facets', 'views'];

    return $dependencies;
  }

}
