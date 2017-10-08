<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Plugin\ResponsiveMenus\CodropsResponsiveMulti.
 */

namespace Drupal\responsive_menus\Plugin\ResponsiveMenus;

use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_menus\ResponsiveMenusPluginBase;
use Drupal\responsive_menus\ResponsiveMenusPluginInterface;

/**
 * Defines the "codrops_responsive_multi" plugin.
 *
 * @ResponsiveMenus(
 *   id = "codrops_responsive_multi",
 *   label = @Translation("ResponsiveMultiLevelMenu (codrops)"),
 *   library = "responsive_menus/codrops_responsive_multi"
 * )
 */
class CodropsResponsiveMulti extends ResponsiveMenusPluginBase implements ResponsiveMenusPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSelectorInfo() {
    return t('Parent of the @ul.  Example: Given <code>@code</code> you would use !use', [
      '@ul'   => '<ul>',
      '@code' => '<div id="parent-div"> <ul class="menu"> </ul> </div>',
      '!use'  => '<strong>#parent-div</strong>',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_menus_codrops_responsive_multi_css_selectors' => '#main-menu',
      'responsive_menus_codrops_responsive_multi_media_size'    => 768,
      'responsive_menus_codrops_responsive_multi_ani_in'        => 'dl-animate-in-1',
      'responsive_menus_codrops_responsive_multi_ani_out'       => 'dl-animate-out-1',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['responsive_menus_codrops_responsive_multi_css_selectors'] = [
      '#type'          => 'textfield',
      '#title'         => t('CSS selectors for which menu to responsify'),
      '#default_value' => $this->getSetting('responsive_menus_codrops_responsive_multi_css_selectors'),
      '#description'   => t('Enter CSS/jQuery selector of menus to responsify.'),
    ];

    $form['responsive_menus_codrops_responsive_multi_media_size'] = [
      '#type'          => 'textfield',
      '#title'         => t('Screen width to respond to'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_codrops_responsive_multi_media_size'),
      '#description'   => t('Width in pixels when we swap out responsive menu e.g. 768'),
    ];

    $form['responsive_menus_codrops_responsive_multi_ani_in'] = [
      '#type'          => 'select',
      '#title'         => t('In-animation'),
      '#options'       => [
        'dl-animate-in-1' => t('One'),
        'dl-animate-in-2' => t('Two'),
        'dl-animate-in-3' => t('Three'),
        'dl-animate-in-4' => t('Four'),
        'dl-animate-in-5' => t('Five'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_codrops_responsive_multi_ani_in'),
    ];

    $form['responsive_menus_codrops_responsive_multi_ani_out'] = [
      '#type'          => 'select',
      '#title'         => t('Out-animation'),
      '#options'       => [
        'dl-animate-out-1' => t('One'),
        'dl-animate-out-2' => t('Two'),
        'dl-animate-out-3' => t('Three'),
        'dl-animate-out-4' => t('Four'),
        'dl-animate-out-5' => t('Five'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_codrops_responsive_multi_ani_out'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings() {
    $js_settings = [
      'selectors'     => $this->getSetting('responsive_menus_codrops_responsive_multi_css_selectors'),
      'media_size'    => $this->getSetting('responsive_menus_codrops_responsive_multi_media_size'),
      'animation_in'  => $this->getSetting('responsive_menus_codrops_responsive_multi_ani_in'),
      'animation_out' => $this->getSetting('responsive_menus_codrops_responsive_multi_ani_out'),
    ];

    return $js_settings;
  }

}
