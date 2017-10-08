<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Plugin\ResponsiveMenus\Sidr.
 */

namespace Drupal\responsive_menus\Plugin\ResponsiveMenus;

use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_menus\ResponsiveMenusPluginBase;
use Drupal\responsive_menus\ResponsiveMenusPluginInterface;

/**
 * Defines the "sidr" plugin.
 *
 * @ResponsiveMenus(
 *   id = "sidr",
 *   label = @Translation("Sidr"),
 *   library = "responsive_menus/sidr"
 * )
 */
class Sidr extends ResponsiveMenusPluginBase implements ResponsiveMenusPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSelectorInfo() {
    return t('Anything (parent of ul preferred).  Example: Given <code>@code</code> you could use !use', [
      '@ul'   => '<ul>',
      '@code' => '<div id="parent-div"> <ul class="menu"> </ul> </div>',
      '!use'  => '<strong>#parent-div or .menu</strong>',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_menus_sidr_css_selectors'   => '#main-menu',
      'responsive_menus_sidr_trigger_txt'     => t('Menu'),
      'responsive_menus_sidr_menu_side'       => 'left',
      'responsive_menus_sidr_menu_theme'      => 'dark',
      'responsive_menus_sidr_animation_speed' => 200,
      'responsive_menus_sidr_media_size'      => 768,
      'responsive_menus_sidr_displace'        => 1,
      'responsive_menus_sidr_renaming'        => 1,
      'responsive_menus_sidr_on_open'         => '',
      'responsive_menus_sidr_on_close'        => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['responsive_menus_sidr_css_selectors'] = [
      '#type'          => 'textarea',
      '#title'         => t('CSS selectors for which menu to responsify'),
      '#default_value' => $this->getSetting('responsive_menus_sidr_css_selectors'),
      '#description'   => t('Enter CSS/jQuery selectors of menus to responsify.  Comma separated or 1 per line'),
    ];

    $form['responsive_menus_sidr_trigger_txt'] = [
      '#type'          => 'textarea',
      '#title'         => t('Text or HTML for trigger button'),
      '#default_value' => $this->getSetting('responsive_menus_sidr_trigger_txt'),
    ];

    $form['responsive_menus_sidr_menu_side'] = [
      '#type'          => 'select',
      '#title'         => t('Position of the menu (left/right)'),
      '#options'       => [
        'left'  => t('Left'),
        'right' => t('Right'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_sidr_menu_side'),
    ];

    $form['responsive_menus_sidr_menu_theme'] = [
      '#type'          => 'select',
      '#title'         => t('Theme'),
      '#options'       => [
        'dark'   => t('Dark'),
        'light'  => t('Light'),
        'custom' => t('Custom'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_sidr_menu_theme'),
      '#description'   => t('Select which <a href="@theme">theme</a> will be used to style the menu. If "Custom" is selected, no CSS will be added; you will need to add your own styles.', ['@theme' => 'http://www.berriart.com/sidr/#themes']),
    ];

    $form['responsive_menus_sidr_animation_speed'] = [
      '#type'          => 'textfield',
      '#title'         => t('Sidr animation speed'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_sidr_animation_speed'),
      '#description'   => t('Speed (in milliseconds) of menu open/close. 1000 = 1 second.'),
    ];

    $form['responsive_menus_sidr_media_size'] = [
      '#type'          => 'textfield',
      '#title'         => t('Screen width to respond to'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_sidr_media_size'),
      '#description'   => t('Width in pixels when we swap out responsive menu e.g. 768'),
    ];

    $form['responsive_menus_sidr_displace'] = [
      '#type'          => 'select',
      '#title'         => t('Displace body content?'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_sidr_displace'),
      '#description'   => t('This setting controls whether the page is pushed when menu is opened.'),
    ];

    $form['responsive_menus_sidr_renaming'] = [
      '#type'          => 'select',
      '#title'         => t('Rename classes'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_sidr_renaming'),
      '#description'   => t('Controls whether Sidr will rename classes within the the selectors specified.'),
    ];

    $form['responsive_menus_sidr_on_open'] = [
      '#type'          => 'textarea',
      '#title'         => t('onOpen callback (function)'),
//      '#description' => t('See !documentation for examples.', ['!documentation' => l(t('Sidr documentation'), 'http://www.berriart.com/sidr/#documentation')]),
      '#default_value' => $this->getSetting('responsive_menus_sidr_on_open'),
    ];

    $form['responsive_menus_sidr_on_close'] = [
      '#type'          => 'textarea',
      '#title'         => t('onClose callback (function)'),
//      '#description' => t('See !documentation for examples.', ['!documentation' => l(t('Sidr documentation'), 'http://www.berriart.com/sidr/#documentation')]),
      '#default_value' => $this->getSetting('responsive_menus_sidr_on_close'),
    ];

    /* Other sidr attributes not implemented:
     *
     * renaming (Boolean) Default: true
     * When filling the sidr with existing content, choose to rename or not the
     * classes and ids.
     *
     * body (String) Default: 'body' [ Version 1.1.0 an above ]
     * For doing the page movement the 'body' element is animated by default,
     * you can select another element to animate with this option.
     *
     * displace (Boolean) Default: true [ Version 1.2.0 an above ]
     * Displace the body content or not.
     */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings() {
    $js_settings = [
      'selectors'   => $this->getSettingArray('responsive_menus_sidr_css_selectors'),
      'trigger_txt' => $this->getSetting('responsive_menus_sidr_trigger_txt'),
      'side'        => $this->getSetting('responsive_menus_sidr_menu_side'),
      'speed'       => $this->getSetting('responsive_menus_sidr_animation_speed'),
      'media_size'  => $this->getSetting('responsive_menus_sidr_media_size'),
      'displace'    => $this->getSetting('responsive_menus_sidr_displace'),
      'renaming'    => $this->getSetting('responsive_menus_sidr_renaming'),
      'onOpen'      => $this->getSetting('responsive_menus_sidr_on_open'),
      'onClose'     => $this->getSetting('responsive_menus_sidr_on_close'),
    ];

    return $js_settings;
  }

}
