<?php

namespace Drupal\commerce_currency_switcher\Form;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CurrencySwitchForm.
 *
 * @package Drupal\commerce_currency_switcher\Form
 */
class CurrencySwitchForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, CartProviderInterface $cartProvider) {
    $this->storage = $entity_type_manager->getStorage('commerce_currency');
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->cartProvider = $cartProvider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currency_switch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $currency_list = '';

    $active_currencies = $this->storage
      ->loadByProperties([
        'status' => TRUE,
      ]);

    // If no currency is imported or active, do not show any form.
    if (empty($active_currencies)) {
      return;
    }

    foreach ($active_currencies as $currency_code => $currency) {
      /* @var \Drupal\commerce_price\Entity\Currency $currency */
      $currency_list[$currency_code] = $currency->getName();
    }

    $session = $this->currentRequest->getSession();
    $selected_currency = $session->has('selected_currency') ? $session->get('selected_currency') : '';

    // If no currency is available in session, set the first currency in list as
    // default one. This helps in Price resolver to return a default price.
    if (empty($selected_currency)) {
      $selected_currency = reset($currency_list);
      $session->set('selected_currency', $selected_currency);
    }

    // Set the active currency list on site in state.
    \Drupal::state()->set('active_currencies', $currency_list);

    $form['currency'] = [
      '#type' => 'select',
      '#options' => $currency_list,
      '#default_value' => $selected_currency,
      '#attributes' => [
        'class' => ['currency-select'],
        'onChange' => ['this.form.submit()'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_currency = $form_state->getValue('currency');

    $session = $this->currentRequest->getSession();
    $session->set('selected_currency', $selected_currency);

    // Save the carts on change of the currency to trigger OrderRefresh.
    $carts = $this->cartProvider->getCarts();
    foreach ($carts as $cart) {
      /* @var \Drupal\commerce_order\Entity\Order $cart */
      $cart->save();
    }
  }

}
