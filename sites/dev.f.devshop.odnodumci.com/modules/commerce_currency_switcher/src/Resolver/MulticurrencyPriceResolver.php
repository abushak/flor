<?php

namespace Drupal\commerce_currency_switcher\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Multicurrency price resolver.
 */
class MulticurrencyPriceResolver implements PriceResolverInterface {

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $configFactory, ModuleHandler $moduleHandler) {
    $this->request = $request_stack->getCurrentRequest();
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $variation, $quantity, Context $context) {

    $session = $this->request->getSession();

    $currency_switch_config = $this->configFactory->getEditable('currency_switch_settings.settings');

    if ($currency_switch_config->get('geoip_enable') && $this->moduleHandler->moduleExists('geoip')) {
      $geo_locator = \Drupal::service('geoip.geolocation');
      $ip_address = \Drupal::request()->getClientIp();
      $country = $geo_locator->geolocate($ip_address);
      if (is_object($country)) {
        $currency_code = $country->getCurrencyCode();

        $active_currencies = \Drupal::state()->get('active_currencies');
        $currency_code = isset($active_currencies[$currency_code]) ? $currency_code : reset($active_currencies);
        $session->set('selected_currency', $currency_code);
      }
    }

    $selected_currency = $session->get('selected_currency');

    $resolved_price_field = 'field_price_' . strtolower($selected_currency);
    if (!empty($selected_currency) && $variation->hasField($resolved_price_field)) {
      return $variation->get($resolved_price_field)->first()->toPrice();
    }

    return '';
  }

}
