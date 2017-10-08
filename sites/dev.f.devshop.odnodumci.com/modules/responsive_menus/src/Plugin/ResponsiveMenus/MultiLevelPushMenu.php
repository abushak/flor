<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Plugin\ResponsiveMenus\MultiLevelPushMenu.
 */

namespace Drupal\responsive_menus\Plugin\ResponsiveMenus;

use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_menus\ResponsiveMenusPluginBase;
use Drupal\responsive_menus\ResponsiveMenusPluginInterface;

/**
 * Defines the "mlpm" plugin.
 *
 * @ResponsiveMenus(
 *   id = "mlpm",
 *   label = @Translation("Multi Level Push Menu"),
 *   library = "responsive_menus/mlpm"
 * )
 */
class MultiLevelPushMenu extends ResponsiveMenusPluginBase implements ResponsiveMenusPluginInterface {

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
      'responsive_menus_mlpm_css_selectors'  => '#main-menu',
      'responsive_menus_mlpm_media_size'     => 768,
      'responsive_menus_mlpm_move_to'        => '#page-wrapper',
      'responsive_menus_mlpm_nav_block'      => 1,
      'responsive_menus_mlpm_nav_block_name' => 'mlpm-menu',
      'responsive_menus_mlpm_push'           => '#page',
      'responsive_menus_mlpm_menu_height'    => '100%',
      'responsive_menus_mlpm_direction'      => 'ltr',
      'responsive_menus_mlpm_mode'           => 'overlap',
      'responsive_menus_mlpm_collapsed'      => 1,
      'responsive_menus_mlpm_full_collapse'  => 0,
      'responsive_menus_mlpm_swipe'          => 'both',
      'responsive_menus_mlpm_decoration'     => [
        'font_awesome' => 1,
        'google_fonts' => 1,
        'back_text'    => 'Back',
        'back_class'   => 'backItemClass',
        'back_icon'    => 'fa fa-angle-right',
        'group_icon'   => 'fa fa-angle-left',
      ],
      'responsive_menus_mlpm_toggle'         => [
        'container' => '',
        'text'      => '',
        'off_menu'  => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['responsive_menus_mlpm_css_selectors'] = [
      '#type'          => 'textfield',
      '#title'         => t('CSS selectors for which menu to responsify'),
      '#default_value' => $this->getSetting('responsive_menus_mlpm_css_selectors'),
      '#description'   => t('Enter CSS/jQuery selector of menus to responsify.'),
    ];

    $form['responsive_menus_mlpm_media_size'] = [
      '#type'          => 'textfield',
      '#title'         => t('Screen width to respond to'),
      '#size'          => 5,
      '#default_value' => $this->getSetting('responsive_menus_mlpm_media_size'),
      '#description'   => t('Width in pixels when we swap out responsive menu e.g. 768 (0 means the responsive menu will always show)'),
    ];

    $form['responsive_menus_mlpm_move_to'] = [
      '#type'          => 'textfield',
      '#title'         => t('CSS selector to move menu to'),
      '#default_value' => $this->getSetting('responsive_menus_mlpm_move_to'),
      '#description'   => t("Enter a CSS/JQuery selector of the container the nav menu will be moved to. This is useful when using a theme you don't want to alter."),
    ];

    $form['responsive_menus_mlpm_nav_block'] = [
      '#type'          => 'select',
      '#title'         => t('Add nav block?'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#default_value' => $this->getSetting('responsive_menus_mlpm_nav_block'),
      '#description'   => t("MLPM requires a nav block to be in place. This can be added using javascript if you don't want to alter your theme."),
    ];

    $form['responsive_menus_mlpm_nav_block_name'] = [
      '#type'          => 'textfield',
      '#title'         => t('Id for nav block'),
      '#default_value' => $this->getSetting('responsive_menus_mlpm_nav_block_name'),
      '#description'   => t('Enter the id of nav block.'),
    ];

    $form['responsive_menus_mlpm_push'] = [
      '#type'          => 'textarea',
      '#title'         => t('CSS selectors of containers to push'),
      '#default_value' => $this->getSetting('responsive_menus_mlpm_push'),
      '#description'   => t('CSS/jQuery selectors of the elements that need to be pushed when expading the MLPM (one per line)'),
    ];

    $form['responsive_menus_mlpm_menu_height'] = [
      '#type'          => 'textfield',
      '#title'         => 'Menu height',
      '#description'   => "Menu height (integer, '%', 'px', 'em').",
      '#default_value' => $this->getSetting('responsive_menus_mlpm_menu_height'),
    ];

    $form['responsive_menus_mlpm_direction'] = [
      '#type'          => 'select',
      '#title'         => t('Sliding direction'),
      '#options'       => [
        'ltr' => t('Left to right'),
        'rtl' => t('Right to left'),
      ],
      '#description'   => '',
      '#default_value' => $this->getSetting('responsive_menus_mlpm_direction'),
    ];

    $form['responsive_menus_mlpm_mode'] = [
      '#type'          => 'select',
      '#title'         => t('Menu sliding mode'),
      '#options'       => [
        'overlap' => t('Overlap'),
        'cover'   => t('Cover'),
      ],
      '#description'   => '',
      '#default_value' => $this->getSetting('responsive_menus_mlpm_mode'),
    ];

    $form['responsive_menus_mlpm_collapsed'] = [
      '#type'          => 'select',
      '#title'         => t('How to load the menu'),
      '#options'       => [
        1 => t('Collapsed'),
        0 => t('Expanded'),
      ],
      '#description'   => 'Initialize menu in collapsed/expanded mode',
      '#default_value' => $this->getSetting('responsive_menus_mlpm_collapsed'),
    ];

    $form['responsive_menus_mlpm_full_collapse'] = [
      '#type'          => 'select',
      '#title'         => t('Full collapse'),
      '#options'       => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#description'   => 'Do you want to fully hide base level holder when collapsed?',
      '#default_value' => $this->getSetting('responsive_menus_mlpm_full_collapse'),
    ];

    $form['responsive_menus_mlpm_swipe'] = [
      '#type'          => 'select',
      '#title'         => t('Swipe mode'),
      '#options'       => [
        'both'        => t('Both'),
        'desktop'     => t('Desktop'),
        'touchscreen' => t('Touchscreen'),
      ],
      '#description'   => '',
      '#default_value' => $this->getSetting('responsive_menus_mlpm_swipe'),
    ];

    $form['responsive_menus_mlpm_decoration'] = [
      '#type'  => 'details',
      '#title' => t('Menu decoration'),
      '#open'  => FALSE,
    ];

    $style = $this->getSetting('responsive_menus_mlpm_decoration');
    $form['responsive_menus_mlpm_decoration']['font_awesome'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Include font awesome'),
      '#description'   => t('By default font awesome is used for the menu icons'),
      '#default_value' => $style['font_awesome'],
    ];

    $form['responsive_menus_mlpm_decoration']['google_fonts'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Include google fonts'),
      '#description'   => t('By default google fonts are used to style this menu.'),
      '#default_value' => $style['google_fonts'],
    ];

    $form['responsive_menus_mlpm_decoration']['back_text'] = [
      '#type'          => 'textfield',
      '#title'         => t('Back text'),
      '#description'   => t('The text that will appear on the back links leading you to previous levels of the menu.'),
      '#default_value' => $style['back_text'],
    ];

    $form['responsive_menus_mlpm_decoration']['back_class'] = [
      '#type'          => 'textfield',
      '#title'         => t('Back item class'),
      '#description'   => t('The class of the back link that leads to the pervious levels of the menu.'),
      '#default_value' => $style['back_class'],
    ];

    $form['responsive_menus_mlpm_decoration']['back_icon'] = [
      '#type'          => 'textfield',
      '#title'         => t('Back item icon'),
      '#description'   => t('The icon used for the back link that leads to previous levels of the menu (default requires font awesome).'),
      '#default_value' => $style['back_icon'],
    ];

    $form['responsive_menus_mlpm_decoration']['group_icon'] = [
      '#type'          => 'textfield',
      '#title'         => t('Group icon'),
      '#description'   => t('The icon used on menu links that lead into new layers of the menu (default requires font awesome).'),
      '#default_value' => $style['group_icon'],
    ];

    $form['responsive_menus_mlpm_toggle'] = [
      '#type'  => 'details',
      '#title' => t('Toggle control'),
      '#open'  => FALSE,
    ];

    $toggle = $this->getSetting('responsive_menus_mlpm_toggle');
    $form['responsive_menus_mlpm_toggle']['container'] = [
      '#type'          => 'textfield',
      '#title'         => t('Menu toggle control container'),
      '#description'   => t('The CSS/jQuery selector you would like an anchor tag that will toggle the menu open and closed (leave blank for no control).'),
      '#default_value' => $toggle['container'],
    ];

    $form['responsive_menus_mlpm_toggle']['text'] = [
      '#type'          => 'textarea',
      '#title'         => t('Menu toggle control text'),
      '#description'   => t('The text/filtered html you would like inside the toggle control'),
      '#default_value' => $toggle['text'],
    ];

    $form['responsive_menus_mlpm_toggle']['off_menu'] = [
      '#type'          => 'textfield',
      '#title'         => t('Container to detect off menu clicks'),
      '#description'   => t('The CSS/jQuery selector that will close the menu when clicked. This is useful for when you want to be able to close the menu by clicking off of the menu.'),
      '#default_value' => $toggle['off_menu'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings() {
    $toggle = $this->getSetting('responsive_menus_mlpm_toggle');
    $style = $this->getSetting('responsive_menus_mlpm_decoration');
    $js_settings = [
      'selectors'        => $this->getSetting('responsive_menus_mlpm_css_selectors'),
      'media_size'       => $this->getSetting('responsive_menus_mlpm_media_size'),
      'move_to'          => $this->getSetting('responsive_menus_mlpm_move_to'),
      'nav_block'        => $this->getSetting('responsive_menus_mlpm_nav_block'),
      'nav_block_name'   => $this->getSetting('responsive_menus_mlpm_nav_block_name'),
      'push'             => explode("\n", $this->getSetting('responsive_menus_mlpm_push')),
      'menu_height'      => $this->getSetting('responsive_menus_mlpm_menu_height'),
      'direction'        => $this->getSetting('responsive_menus_mlpm_direction'),
      'mode'             => $this->getSetting('responsive_menus_mlpm_mode'),
      'collapsed'        => $this->getSetting('responsive_menus_mlpm_collapsed'),
      'full_collapse'    => $this->getSetting('responsive_menus_mlpm_full_collapse'),
      'swipe'            => $this->getSetting('responsive_menus_mlpm_swipe'),
      'toggle_container' => $toggle['container'],
      'toggle_text'      => isset($toggle['text']) ? check_markup($toggle['text'], 'filtered_html') : '',
      'off_menu'         => $toggle['off_menu'],
      'back_text'        => $style['back_text'],
      'back_class'       => $style['back_class'],
      'back_icon'        => $style['back_icon'],
      'group_icon'       => $style['group_icon'],
    ];

    return $js_settings;
  }

}

/**
 * Get the css to include for the multi-level-push menu based on the
 * configuration.
 */
//function _responsive_menus_mlpm_get_css() {
//  $path = drupal_get_path('module', 'responsive_menus') . '/styles';
////  $style = variable_get('responsive_menus_mlpm_decoration', [
////    'font_awesome' => 1,
////    'google_fonts' => 1,
////  ]);
//  $mlpm_css = [];
//  if (!isset($style['google_fonts']) || $style['google_fonts']) {
//    $mlpm_css[] = '//fonts.googleapis.com/css?family=Open+Sans+Condensed:300,300italic,700&subset=latin,cyrillic-ext,latin-ext,cyrillic';
//  }
//  if (!isset($style['font_awesome']) || $style['font_awesome']) {
//    $mlpm_css[] = '//netdna.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.min.css?the-file-wont-load-without-a-parameter';
//  }
//  $mlpm_css[] = $path . '/mlpm/css/jquery.multilevelpushmenu.css';
//  return $mlpm_css;
//}
