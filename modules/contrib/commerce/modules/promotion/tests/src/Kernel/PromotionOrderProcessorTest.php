<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the promotion order processor.
 *
 * @group commerce
 */
class PromotionOrderProcessorTest extends CommerceKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $this->user = $this->createUser();

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the order amount condition.
   */
  public function testOrderTotal() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $promotion->save();

    $this->assertNotEmpty($promotion->applies($this->order));
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);

    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests the coupon based promotion processor.
   */
  public function testCouponPromotion() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion_with_coupon = Promotion::create([
      'name' => 'Promotion (with coupon)',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion_with_coupon->save();

    $coupon = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion_with_coupon->get('coupons')->appendItem($coupon);
    $promotion_with_coupon->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);

    $this->assertEquals(0, count($this->order->getAdjustments()));

    $this->order->get('coupons')->appendItem($coupon);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);

    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());
  }

}
