<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\ResponsiveMenusPluginInterface.
 */

namespace Drupal\responsive_menus;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\PluginSettingsInterface;

/**
 * Interface ResponsiveMenusInterface.
 *
 * @package Drupal\responsive_menus
 */
interface ResponsiveMenusPluginInterface extends PluginSettingsInterface {

  /**
   * Provide UI with plugins selector information.
   */
  public static function getSelectorInfo();

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Get Drupal Javscript settings array.
   *
   * @return array
   *   The Javascript settings array.
   */
  public function getJsSettings();

}
