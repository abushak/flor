<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Plugin\ResponsiveMenus\MeanMenu.
 */

namespace Drupal\responsive_menus\Plugin\ResponsiveMenus;

use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_menus\ResponsiveMenusPluginBase;
use Drupal\responsive_menus\ResponsiveMenusPluginInterface;

/**
 * Defines the "mean_menu" plugin.
 *
 * @ResponsiveMenus(
 *   id = "mean_menu",
 *   label = @Translation("Mean Menu"),
 *   library = "responsive_menus/mean_menu"
 * )
 */
class MeanMenu extends ResponsiveMenusPluginBase implements ResponsiveMenusPluginInterface {

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
      'responsive_menus_mean_menu_css_selectors'   => '#main-menu',
      'responsive_menus_mean_menu_container'       => 'body',
      'responsive_menus_mean_menu_trigger_txt'     => '<span /><span /><span />',
      'responsive_menus_mean_menu_close_txt'       => 'X',
      'responsive_menus_mean_menu_close_size'      => '18px',
      'responsive_menus_mean_menu_position'        => 'right',
      'responsive_menus_mean_menu_media_size'      => 480,
      'responsive_menus_mean_menu_show_children'   => 1,
      'responsive_menus_mean_menu_expand_children' => 1,
      'responsive_menus_mean_menu_expand_txt'      => '+',
      'responsive_menus_mean_menu_contract_txt'    => '-',
      'responsive_menus_mean_menu_remove_attrs'    => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['responsive_menus_mean_menu_css_selectors'] = [
      '#type'          => 'textfield',
      '#title'         => t('CSS selectors for which menu to responsify'),
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_css_selectors'),
      '#description'   => t('Enter CSS/jQuery selector of menus to responsify.'),
    ];

    $form['responsive_menus_mean_menu_container'] = [
      '#type'          => 'textfield',
      '#title'         => t('CSS selector for where to attach the menu on the page'),
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_container'),
      '#description'   => t('Enter CSS/jQuery selector of where MeanMenu gets attached.'),
    ];

    $form['responsive_menus_mean_menu_trigger_txt'] = [
      '#type'          => 'textarea',
      '#title'         => t('Text or HTML for trigger button'),
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_trigger_txt'),
      '#description'   => t('Default of 3 spans will show the triple bars (!bars).', ['!bars' => '☰']),
    ];

    $form['responsive_menus_mean_menu_close_txt'] = [
      '#type'          => 'textarea',
      '#title'         => t('Text or HTML for close button'),
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_close_txt'),
    ];

    $form['responsive_menus_mean_menu_close_size'] = [
      '#type'          => 'textfield',
      '#title'         => t('Size of close button'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_close_size'),
      '#description'   => t('Size in px, em, %'),
    ];

    $form['responsive_menus_mean_menu_position'] = [
      '#type'          => 'select',
      '#title'         => t('Position of the open/close buttons'),
      '#options'       => [
        'right'  => t('right'),
        'left'   => t('left'),
        'center' => t('center'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_position'),
    ];

    $form['responsive_menus_mean_menu_media_size'] = [
      '#type'          => 'textfield',
      '#title'         => t('Screen width to respond to'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_media_size'),
      '#description'   => t('Width in pixels when we swap out responsive menu e.g. 768'),
    ];

    $form['responsive_menus_mean_menu_show_children'] = [
      '#type'          => 'select',
      '#title'         => t('Allow multi-level menus'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_show_children'),
    ];

    $form['responsive_menus_mean_menu_expand_children'] = [
      '#type'          => 'select',
      '#title'         => t('Ability to expand & collapse children'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_expand_children'),
    ];

    $form['responsive_menus_mean_menu_expand_txt'] = [
      '#type'          => 'textfield',
      '#title'         => t('Text for the expand children button'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_expand_txt'),
    ];

    $form['responsive_menus_mean_menu_contract_txt'] = [
      '#type'          => 'textfield',
      '#title'         => t('Text for the collapse children button'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_contract_txt'),
    ];

    $form['responsive_menus_mean_menu_remove_attrs'] = [
      '#type'          => 'select',
      '#title'         => t('Temporarily remove other classes & IDs (Recommended)'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_mean_menu_remove_attrs'),
      '#description'   => t('This will help ensure the style of Mean Menus.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings() {
    $js_settings = [
      'selectors'       => $this->getSetting('responsive_menus_mean_menu_css_selectors'),
      'container'       => $this->getSetting('responsive_menus_mean_menu_container'),
      'trigger_txt'     => $this->getSetting('responsive_menus_mean_menu_trigger_txt'),
      'close_txt'       => $this->getSetting('responsive_menus_mean_menu_close_txt'),
      'close_size'      => $this->getSetting('responsive_menus_mean_menu_close_size'),
      'position'        => $this->getSetting('responsive_menus_mean_menu_position'),
      'media_size'      => $this->getSetting('responsive_menus_mean_menu_media_size'),
      'show_children'   => $this->getSetting('responsive_menus_mean_menu_show_children'),
      'expand_children' => $this->getSetting('responsive_menus_mean_menu_expand_children'),
      'expand_txt'      => $this->getSetting('responsive_menus_mean_menu_expand_txt'),
      'contract_txt'    => $this->getSetting('responsive_menus_mean_menu_contract_txt'),
      'remove_attrs'    => $this->getSetting('responsive_menus_mean_menu_remove_attrs'),
    ];

    return $js_settings;
  }

}
