<?php

namespace Drupal\core_views_facets\EventSubscriber;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\Core\Plugin\PluginBase;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Response subscriber to handle AJAX responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * The facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a AjaxResponseSubscriber object.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(DefaultFacetManager $facet_manager, RequestStack $request_stack) {
    $this->facetManager = $facet_manager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Only alter views ajax responses.
    if (!($response instanceof ViewAjaxResponse)) {
      return;
    }

    $view = $response->getView();

    // Views removes processed arguments and parameters from the request,
    // so every facet but the first one would be wrong.
    $view_parameters = $view->getRequest()->query->all();

    $views_arguments = &drupal_static('core_views_contextual_filter_ajax_arguments');
    $views_arguments = $view->args;

    $exposed_filter_rendered_status = &drupal_static('core_views_exposed_filter_ajax_rendered_status', TRUE);
    $exposed_filter_rendered_status = TRUE;
    $contextual_filter_rendered_status = &drupal_static('core_views_contextual_filter_ajax_rendered_status', TRUE);
    $contextual_filter_rendered_status = TRUE;

    $exposed_facets = $this->facetManager->getFacetsByFacetSourceId('core_views_exposed_filter' . PluginBase::DERIVATIVE_SEPARATOR . $view->id() . '__' . $view->current_display);

    if (empty($exposed_facets)) {
      $exposed_filter_rendered_status = FALSE;
    }

    $contextual_facets = $this->facetManager->getFacetsByFacetSourceId('core_views_contextual_filter' . PluginBase::DERIVATIVE_SEPARATOR . $view->id() . '__' . $view->current_display);

    if (empty($contextual_facets)) {
      $contextual_filter_rendered_status = FALSE;
    }

    /** @var \Drupal\facets\FacetInterface[] $facets */
    $facets = array_merge($exposed_facets, $contextual_facets);
    foreach ($facets as $facet) {
      $this->request->query->add($view_parameters);
      $build = $this->facetManager->build($facet);
      if (empty($build)) {
        $build = ['#markup' => '<span data-drupal-facet-id="' . $facet->id() . '" />'];
      }
      $response->addCommand(new ReplaceCommand('[data-drupal-facet-id="' . $facet->id() . '"]', $build));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before main_content_view_subscriber.
    return [KernelEvents::RESPONSE => [['onResponse', 1]]];
  }

}
