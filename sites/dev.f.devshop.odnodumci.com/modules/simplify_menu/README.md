# Simplify Menu
The simplify_menu module uses a TwigExtension to gain access to Drupal's main menu's (or any other menu for that matter), render array so it can be accessed from a twig template. Among the many advantages of having full control of the menu's render array in a twig template is the ability to customize the markup for your menus to ensure they are accessible and comply with standards.

## How to use
```// Get menu items
{% set items = simplify_menu('main') %}

// Iterate menu tree
<nav class="navigation__items">
  {% for menu_item in items.menu_tree %}
    <li class="navigation__item">
      <a href="{{ menu_item.url }}">{{ menu_item.text }}</a>
    </li>
  {% endfor %}
</nav>
```
