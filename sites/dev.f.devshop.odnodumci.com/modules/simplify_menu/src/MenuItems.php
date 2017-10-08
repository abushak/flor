<?php

namespace Drupal\simplify_menu;

use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Class MenuItems.
 *
 * @package \Drupal\simplify_menu
 */
class MenuItems {

  /**
   * MenuLinkTree definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * MenuItems constructor.
   *
   * @param MenuLinkTree $menu_link_tree
   *   The MenuLinkTree service.
   */
  public function __construct(MenuLinkTree $menu_link_tree) {
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * Map menu tree into an array.
   *
   * @param array $links
   *   The array of menu tree links.
   * @param string $submenuKey
   *   The key for the submenu to simplify.
   *
   * @return array
   *   The simplified menu tree array.
   */
  protected function simplifyLinks(array $links, $submenuKey = 'submenu') {
    $result = [];
    foreach ($links as $item) {
      $simplifiedLink = [
        'text' => $item->link->getTitle(),
        'url' => $item->link->getUrlObject()->toString(),
      ];

      if ($item->hasChildren) {
        $simplifiedLink[$submenuKey] = $this->simplifyLinks($item->subtree);
      }
      $result[] = $simplifiedLink;
    }

    return $result;
  }

  /**
   * Get header menu links.
   *
   * @params string $menuId
   *   Menu drupal id.
   *
   * @return array
   *   Render array of menu items.
   */
  public function getMenuTree($menuId = 'main') {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $headerTreeLoad = $this->menuLinkTree->load($menuId, $parameters);
    $headerTransform = $this->menuLinkTree->transform($headerTreeLoad, $manipulators);

    return [
      'menu_tree' => $this->simplifyLinks($headerTransform),
    ];
  }

}
