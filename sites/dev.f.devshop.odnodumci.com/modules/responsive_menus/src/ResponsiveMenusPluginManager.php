<?php

/**
 * @file
 * Contains \Drupal\responsive_menus\ResponsiveMenusPluginManager.
 */

namespace Drupal\responsive_menus;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ResponsiveMenusManager.
 *
 * @package Drupal\responsive_menus
 */
class ResponsiveMenusPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ResponsiveMenus', $namespaces, $module_handler, 'Drupal\responsive_menus\ResponsiveMenusPluginInterface', 'Drupal\responsive_menus\Annotation\ResponsiveMenus');
    $this->alterInfo('responsive_menus_styles');
    $this->setCacheBackend($cache_backend, 'responsive_menus_plugins');
  }

}
