<?php

namespace Drupal\commerce_currency_switcher\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class CurrencySwitchSettingsForm.
 *
 * @package Drupal\commerce_currency_switcher\Form
 */
class CurrencySwitchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('currency_switch_settings.settings');

    $url = Url::fromUri('https://www.drupal.org/project/geoip', ['attributes' => ['target' => '_blank']]);
    $link = Link::fromTextAndUrl('Geoip module', $url);

    $form['geoip_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Geoip based currency selection.'),
      '#default_value' => $config->get('geoip_enable'),
      '#description' => $this->t('This setting will only work if @link is enabled and configured correctly.', ['@link' => $link->toString()]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'currency_switch_settings_form';
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('currency_switch_settings.settings')
      ->set('geoip_enable', $form_state->getValue('geoip_enable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['currency_switch_settings.settings'];
  }

}
