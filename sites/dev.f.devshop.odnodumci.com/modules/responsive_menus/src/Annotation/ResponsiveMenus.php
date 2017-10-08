<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\Annotation\ResponsiveMenus.
 */

namespace Drupal\responsive_menus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class ResponsiveMenus.
 *
 * Plugin Namespace: Plugin\ResponsiveMenus
 *
 * @package Drupal\responsive_menus\Annotation
 *
 * @Annotation
 */
class ResponsiveMenus extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Responsive Menus plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The plugin library.
   *
   * @var string
   */
  public $library;

}
