<?php

namespace Drupal\core_views_facets\Plugin\facets\url_processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\UrlProcessor\UrlProcessorPluginBase;
use Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsExposedFilter;
use Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsContextualFilter;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A url processor for views exposed filters.
 *
 * The core_views_facets url processor builds urls as the views exposed filters
 * expect them. We have to adhere to the urls that views uses to be able to use
 * the processing trough views.
 *
 * @FacetsUrlProcessor(
 *   id = "core_views_url_processor",
 *   label = @Translation("Core views url processor"),
 *   description = @Translation("Formats the query URL so views can process it."),
 * )
 */
class CoreViewsUrlProcessor extends UrlProcessorPluginBase {

  /**
   * The current view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $currentView;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new instance of the class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object for the current request.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The views storage.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, ViewExecutableFactory $executable_factory, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request);

    $this->executableFactory = $executable_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getMasterRequest(),
      $container->get('views.executable'),
      $container->get('entity_type.manager')->getStorage('view')
    );
  }

  /**
   * A string that separates the filters in the query string.
   */
  const SEPARATOR = ':';

  /**
   * An array of active filters.
   *
   * @var string[]
   *   An array containing the active filters.
   */
  protected $activeFilters = [];

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {
    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }

    $source = $facet->getFacetSource();

    if (
      $source instanceof CoreViewsExposedFilter
      || $source instanceof CoreViewsContextualFilter
    ) {
      $map = $source->getViewsArgumentsMap();
      // Load the view.
      /** @var \Drupal\views\ViewEntityInterface $view */
      $view = $this->storage->load($source->pluginDefinition['view_id']);
      $this->currentView = $this->executableFactory->get($view);
      $this->currentView->setDisplay($source->pluginDefinition['view_display']);

      switch (TRUE) {
        case $source instanceof CoreViewsExposedFilter:
          // First get the current list of get parameters.
          $get_params = $this->request->query;

          $views_filter = $source->getViewsFilterDefinition($facet->getFieldIdentifier());
          $views_filter_parameter = empty($views_filter['expose']) ? $facet->getFieldIdentifier() : $views_filter['expose']['identifier'];

          /** @var \Drupal\facets\Result\ResultInterface $result */
          foreach ($results as &$result) {
            $result_get_params = clone $get_params;

            $active_values = $result_get_params->get($views_filter_parameter, $views_filter['expose']['multiple'] ? [] : '', TRUE);

            // If the value is active, remove the filter string from parameters.
            if ($result->isActive()) {
              if ($views_filter['expose']['multiple']) {
                if (($key = array_search($result->getRawValue(), $active_values)) !== FALSE) {
                  unset($active_values[$key]);
                }
              }
              else {
                if ($active_values == $result->getRawValue()) {
                  $active_values = '';
                }
              }
            }
            // If the value is not active, add the filter string.
            else {
              if ($views_filter['expose']['multiple']) {
                $active_values = [$result->getRawValue()];
              }
              else {
                $active_values = $result->getRawValue();
              }
            }

            if (
              $active_values
              || $active_values == '0'
            ) {
              $result_get_params->set($views_filter_parameter, $active_values);
            }
            else {
              $result_get_params->remove($views_filter_parameter);
            }

            $request_arguments = [];
            foreach ($map as $views_argument_id => $current_argument) {
              $request_arguments[] = $current_argument['value'];
            }
            $url = $this->currentView->getUrl($request_arguments);

            // Add existing additional parameters, except pager.
            $additional_parameters = $result_get_params->all();
            unset($additional_parameters['page']);
            unset($additional_parameters['ajax_page_state']);
            unset($additional_parameters['_wrapper_format']);
            $url->setOption('query', $additional_parameters);

            $result->setUrl($url);
          }

          return $results;

        case $source instanceof CoreViewsContextualFilter:
          /** @var \Drupal\facets\Result\ResultInterface $result */
          foreach ($results as &$result) {
            $request_arguments = [];
            foreach ($map as $views_argument_id => $current_argument) {
              if ($views_argument_id == $facet->getFieldIdentifier()) {
                $request_arguments[] = $result->isActive() ? $current_argument['neutral_value'] : $result->getRawValue();
              }
              else {
                $request_arguments[] = $current_argument['value'];
              }
            }

            $url = $this->currentView->getUrl($request_arguments);

            // Add existing additional parameters, except pager.
            $additional_parameters = $this->request->query->all();
            unset($additional_parameters['page']);
            unset($additional_parameters['ajax_page_state']);
            unset($additional_parameters['_wrapper_format']);
            $url->setOption('query', $additional_parameters);

            $result->setUrl($url);
          }

          return $results;

        default:
          return [];
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItems(FacetInterface $facet) {
    $source = $facet->getFacetSource();

    switch (TRUE) {
      case $source instanceof CoreViewsExposedFilter:

        $views_filter = $source->getViewsFilterDefinition($facet->getFieldIdentifier());
        $views_filter_parameter = empty($views_filter['expose']) ? $facet->getFieldIdentifier() : $views_filter['expose']['identifier'];

        if ($this->request->query->has($views_filter_parameter)) {
          if ($views_filter['expose']['multiple']) {
            foreach ($this->request->query->get($views_filter_parameter, [], TRUE) as $value) {
              $facet->setActiveItem(trim($value, '"'));
            }
          }
          else {
            $value = $this->request->query->get($views_filter_parameter, NULL, TRUE);
            if (isset($value) && $value !== '') {
              $facet->setActiveItem($value);
            }
          }
        }

        break;

      case $source instanceof CoreViewsContextualFilter:
        $map = $source->getViewsArgumentsMap();

        if (isset($map[$facet->getFieldIdentifier()])) {
          $current_argument = $map[$facet->getFieldIdentifier()];
          if ($current_argument['value'] != $current_argument['neutral_value']) {
            $facet->setActiveItem($current_argument['value']);
          }
        }
        break;

      default:
        return;
    }
  }

}
