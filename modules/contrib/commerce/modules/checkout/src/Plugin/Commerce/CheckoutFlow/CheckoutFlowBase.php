<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the base checkout flow class.
 *
 * Checkout flows should extend this class only if they don't want to use
 * checkout panes. Otherwise they should extend CheckoutFlowWithPanesBase.
 */
abstract class CheckoutFlowBase extends PluginBase implements CheckoutFlowInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new CheckoutFlowBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->order = $route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousStepId($step_id) {
    $step_ids = array_keys($this->getVisibleSteps());
    $current_index = array_search($step_id, $step_ids);
    return isset($step_ids[$current_index - 1]) ? $step_ids[$current_index - 1] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStepId($step_id) {
    $step_ids = array_keys($this->getVisibleSteps());
    $current_index = array_search($step_id, $step_ids);
    return isset($step_ids[$current_index + 1]) ? $step_ids[$current_index + 1] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function redirectToStep($step_id) {
    $this->order->set('checkout_step', $step_id);
    if ($step_id == 'complete') {
      $transition = $this->order->getState()->getWorkflow()->getTransition('place');
      $this->order->getState()->applyTransition($transition);
    }
    $this->order->save();
    throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $step_id,
    ])->toString());
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Each checkout flow plugin defines its own steps.
    // These two steps are always expected to be present.
    return [
      'payment' => [
        'label' => $this->t('Payment'),
        'next_label' => $this->t('Pay and complete purchase'),
        'has_sidebar' => FALSE,
        'hidden' => TRUE,
      ],
      'complete' => [
        'label' => $this->t('Complete'),
        'next_label' => $this->t('Pay and complete purchase'),
        'has_sidebar' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleSteps() {
    // All steps are visible by default.
    return $this->getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_checkout_progress' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['display_checkout_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display checkout progress'),
      '#description' => $this->t('Used by the checkout progress block to determine visibility.'),
      '#default_value' => $this->configuration['display_checkout_progress'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['display_checkout_progress'] = $values['display_checkout_progress'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'commerce_checkout_flow';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_checkout_flow_' . $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL) {
    // The $step_id argument is optional only because PHP disallows adding
    // required arguments to an existing interface's method.
    if (empty($step_id)) {
      throw new \InvalidArgumentException('The $step_id cannot be empty.');
    }
    if ($form_state->isRebuilding()) {
      // Ensure a fresh order, in case an ajax submit has modified it.
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      $this->order = $order_storage->load($this->order->id());
    }

    $steps = $this->getVisibleSteps();
    $form['#tree'] = TRUE;
    $form['#step_id'] = $step_id;
    $form['#title'] = $steps[$step_id]['label'];
    $form['#theme'] = ['commerce_checkout_form'];
    $form['#attached']['library'][] = 'commerce_checkout/form';
    if ($this->hasSidebar($step_id)) {
      $form['sidebar']['order_summary'] = [
        '#type' => 'view',
        '#name' => 'commerce_checkout_order_summary',
        '#display_id' => 'default',
        '#arguments' => [$this->order->id()],
        '#embed' => TRUE,
      ];
    }
    $form['actions'] = $this->actions($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($next_step_id = $this->getNextStepId($form['#step_id'])) {
      $this->order->set('checkout_step', $next_step_id);
      $form_state->setRedirect('commerce_checkout.form', [
        'commerce_order' => $this->order->id(),
        'step' => $next_step_id,
      ]);

      if ($next_step_id == 'complete') {
        // Place the order.
        $transition = $this->order->getState()->getWorkflow()->getTransition('place');
        $this->order->getState()->applyTransition($transition);
      }
    }

    $this->order->save();
  }

  /**
   * Gets whether the given step has a sidebar.
   *
   * @param string $step_id
   *   The step ID.
   *
   * @return bool
   *   TRUE if the given step has a sidebar, FALSE otherwise.
   */
  protected function hasSidebar($step_id) {
    $steps = $this->getVisibleSteps();
    return !empty($steps[$step_id]['has_sidebar']);
  }

  /**
   * Builds the actions element for the current form.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The actions element.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $steps = $this->getVisibleSteps();
    $next_step_id = $this->getNextStepId($form['#step_id']);
    $previous_step_id = $this->getPreviousStepId($form['#step_id']);
    $has_next_step = $next_step_id && isset($steps[$next_step_id]['next_label']);
    $has_previous_step = $previous_step_id && isset($steps[$previous_step_id]['previous_label']);

    $actions = [
      '#type' => 'actions',
      '#access' => $has_next_step,
    ];
    if ($has_next_step) {
      $actions['next'] = [
        '#type' => 'submit',
        '#value' => $steps[$next_step_id]['next_label'],
        '#button_type' => 'primary',
        '#submit' => ['::submitForm'],
      ];
      if ($has_previous_step) {
        $label = $steps[$previous_step_id]['previous_label'];
        $actions['next']['#suffix'] = Link::createFromRoute($label, 'commerce_checkout.form', [
          'commerce_order' => $this->order->id(),
          'step' => $previous_step_id,
        ])->toString();
      }
    }

    return $actions;
  }

}
