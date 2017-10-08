<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Form\ResponsiveMenusAdminForm.
 */

namespace Drupal\responsive_menus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_menus\ResponsiveMenusPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ResponsiveMenusAdminForm.
 *
 * @package Drupal\responsive_menus\Form
 */
class ResponsiveMenusAdminForm extends ConfigFormBase {

  /**
   * The Responsive Menus plugin manager.
   *
   * @var \Drupal\responsive_menus\ResponsiveMenusPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ResponsiveMenusPluginManager $plugin_manager) {
    parent::__construct($config_factory);

    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.responsive_menus')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_menus_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'responsive_menus.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('responsive_menus.configuration');

    // Gather enabled styles.
    $styles = $this->pluginManager->getDefinitions();
    $style_options = [];
    foreach ($styles as $style => $values) {
      $style_options[$style] = $values['label'];
    }

    // Get style settings form elements from ajax or the currently enabled
    // style.
    $current_style = $config->get('style');
    if (!empty($form_state->getValue('responsive_menus_style'))) {
      $current_style = $form_state->getValue('responsive_menus_style');
    }

    // Reminders about jQuery requirements if applicable.
//    $form['responsive_menus_no_jquery_update'] = [
//      '#type'          => 'checkbox',
//      '#title'         => t('I will provide my own jQuery library.'),
//      '#description'   => t("If the style you want requires newer jQuery version and you don't want to use jquery_update module."),
//      '#default_value' => $config->get('no_jquery_update'),
//    ];

    // Ignore admin pages option.
    $form['responsive_menus_ignore_admin'] = [
      '#type'          => 'checkbox',
      '#title'         => 'Ignore admin pages?',
      '#default_value' => $config->get('ignore_admin'),
    ];

//    $jq_update_ignore = $form['responsive_menus_no_jquery_update']['#default_value'];
//    $style_info = responsive_menus_style_load($current_style, $jq_update_ignore);
    $style_plugin = $this->pluginManager->createInstance($current_style, ['settings' => $config->get('style_settings')]);

    $form['responsive_menus_style'] = [
      '#type'          => 'select',
      '#title'         => t('Responsive menu style'),
      '#options'       => $style_options,
      '#default_value' => $current_style,
      '#ajax'          => [
        'callback' => '::ajax',
        'wrapper'  => 'rm-style-options',
        'method'   => 'replace',
        'effect'   => 'fade',
      ],
    ];
    $form['responsive_menus_style_settings'] = [
      '#title'       => t('Style settings'),
      '#description' => t('Settings for chosen menu style.'),
      '#prefix'      => '<div id="rm-style-options">',
      '#suffix'      => '</div>',
      '#type'        => 'detail',
      '#tree'        => TRUE,
    ];
    // Which selector to use info.
    if (!empty($style_plugin->getSelectorInfo())) {
      $form['responsive_menus_style_settings']['selector_info'] = [
        '#type'   => 'item',
        '#title'  => t('Selector(s) to use for this style:'),
        '#markup' => '<div class="messages status">' . $style_plugin->getSelectorInfo() . '</div>',
      ];
    }
    // Build additional style settings from style plugins.
    foreach ($style_plugin->settingsForm([], $form_state) as $name => $element) {
      $form['responsive_menus_style_settings'][$name] = $element;
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => t('Save configuration'),
    ];

//    if (!empty($_POST) && form_get_errors()) {
//      drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
//    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The settings form array.
   * @param FormStateInterface $form_state
   *   The settings form state object.
   */
  public function ajax(array &$form, FormStateInterface $form_state) {
    return $form['responsive_menus_style_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('responsive_menus.configuration')
      ->set('style', $values['responsive_menus_style'])
      ->set('no_jquery_update', $values['responsive_menus_no_jquery_update'])
      ->set('ignore_admin', $values['responsive_menus_ignore_admin'])
      ->set('style_settings', $values['responsive_menus_style_settings'])
      ->save();

//  // Clear libraries cache if Sidr style in use to allow theme to be updated.
//  if ($form_state['values']['responsive_menus_style'] == 'sidr') {
//    cache_clear_all('*', 'cache_libraries', TRUE);
//  }

    drupal_set_message(t('The configuration options have been saved.'));

    parent::submitForm($form, $form_state);
  }

}