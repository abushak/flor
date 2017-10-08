<?php

namespace Drupal\commerce_variation_add_to_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Core\Link;

/**
 * Variation add to cart form controller.
 */
class VariationAddToCart extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Add item to cart.
   */
  public function addItem() {
    // Get item data from post request.
    $product_id = (integer) \Drupal::request()->request->get('product_id');
    $variation_id = (integer) \Drupal::request()->request->get('variation_id');
    $quantity = (integer) \Drupal::request()->request->get('quantity');
    $destination = \Drupal::request()->request->get('destination');
    if (empty($destination)) {
      $destination = '/cart';
    }

    if ($product_id > 0 && $variation_id > 0 && $quantity > 0) {
      // Load product variation and get store.
      $variation = ProductVariation::load($variation_id);
      $variation_price = $variation->getPrice();
      $stores = $variation->getStores();
      $store = reset($stores);

      $cart = $this->cartProvider->getCart('default', $store);
      // Create cart for user if it already doesn't exist.
      if (!$cart) {
        $cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $store);
      }

      $order_item = OrderItem::create([
        'type' => 'default',
        'purchased_entity' => (string) $variation_id,
        'quantity' => $quantity,
        'unit_price' => $variation_price,
      ]);
      $order_item->save();
      $this->cartManager->addOrderItem($cart, $order_item);

      // Redirect back.
      drupal_set_message($this->t('Product added to @cart-link.', [
        '@cart-link' => Link::createFromRoute($this->t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')->toString(),
      ]), 'status', TRUE);

      return new RedirectResponse($destination);
    }

    drupal_set_message($this->t('Product not added to your cart.'), 'error', TRUE);
    return new RedirectResponse($destination);
  }

}
