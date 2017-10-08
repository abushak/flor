<?php

namespace Drupal\commerce_currency_switcher\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides Commerce currency switch block.
 *
 * @Block(
 *   id = "commerce_curency_switcher",
 *   admin_label = @Translation("Commerce currency switch."),
 *   category = @Translation("Blocks")
 * )
 */
class CurrencySwitchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $markup = [];

    $currencySwitchForm = \Drupal::formBuilder()
      ->getForm('Drupal\commerce_currency_switcher\Form\CurrencySwitchForm');

    $markup['form'] = $currencySwitchForm;

    return $markup;
  }

}
