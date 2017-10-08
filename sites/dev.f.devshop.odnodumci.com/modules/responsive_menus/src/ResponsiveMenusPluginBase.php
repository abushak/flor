<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\ResponsiveMenusPluginBase.
 */

namespace Drupal\responsive_menus;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ResponsiveMenusPluginBase.
 *
 * @package Drupal\responsive_menus
 */
abstract class ResponsiveMenusPluginBase extends PluginSettingsBase implements ResponsiveMenusPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSelectorInfo() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings() {
    return [];
  }

  /**
   * Return array of selectors for JS settings.
   *
   * @params string $name
   *   The settings name.
   *
   * @return array
   *   Array of settings to pass with drupal_add_js().
   */
  protected function getSettingArray($name) {
    $selectors = $this->getSetting($name);
    $delimiter = ', ';
    // Strip out carriage returns.
    $selectors = str_replace("\r", '', $selectors);
    // Replace new lines with delimiter.
    $selectors = str_replace("\n", $delimiter, $selectors);
    // Explode to include original delimited.
    $values = explode($delimiter, $selectors);
    $values = array_filter($values);

    return $values;
  }

}
