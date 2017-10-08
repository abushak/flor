<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests integration with cart events.
 *
 * @group commerce
 */
class CartIntegrationTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

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
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The log view builder.
   *
   * @var \Drupal\commerce_log\LogViewBuilder
   */
  protected $logViewBuilder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_log',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_log');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $this->user = $this->createUser();
    $this->logStorage = $this->container->get('entity_type.manager')->getStorage('commerce_log');
    $this->logViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_log');

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $this->variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => 'Testing product',
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
  }

  /**
   * Tests that a log is generated when an order is placed.
   */
  public function testAddedToCart() {
    $this->enableCommerceCart();
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);
    $this->cartManager->addEntity($cart, $this->variation);

    $logs = $this->logStorage->loadByEntity($cart);
    $this->assertEquals(1, count($logs));
    $log = reset($logs);
    $build = $this->logViewBuilder->view($log);
    $this->render($build);
    $this->assertText("{$this->variation->label()} added to the cart.");
  }

  /**
   * Tests that a log is generated when an order is placed.
   */
  public function testRemovedFromCart() {
    $this->enableCommerceCart();
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);
    $order_item = $this->cartManager->addEntity($cart, $this->variation);
    $this->cartManager->removeOrderItem($cart, $order_item);

    $logs = $this->logStorage->loadByEntity($cart);
    $this->assertEquals(2, count($logs));
    $log = end($logs);
    $build = $this->logViewBuilder->view($log);
    $this->render($build);

    $this->assertText("{$this->variation->label()} removed from the cart.");
  }

  /**
   * Enables commerce_cart for tests.
   *
   * Due to issues with hook_entity_bundle_create, we need to run this here
   * and can't put commerce_cart in $modules.
   *
   * See https://www.drupal.org/node/2711645
   *
   * @todo patch core so it doesn't explode in Kernel tests.
   * @todo remove Cart dependency in Checkout
   */
  protected function enableCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');
    $this->container->get('entity.definition_update_manager')->applyUpdates();
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
  }

}
