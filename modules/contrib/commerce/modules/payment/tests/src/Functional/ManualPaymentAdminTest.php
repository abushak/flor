<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the admin UI for payments of type 'payment_manual'.
 *
 * @group commerce
 */
class ManualPaymentAdminTest extends CommerceBrowserTestBase {

  /**
   * A manual payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * The base admin payment uri.
   *
   * @var string
   */
  protected $paymentUri;

  /**
   * The admin's order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_product',
    'commerce_payment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_payment_gateway',
      'administer commerce_payment',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'manual',
      'label' => 'Manual example',
      'plugin' => 'manual',
    ]);
    $this->paymentGateway->getPlugin()->setConfiguration([
      'display_label' => 'Cash on delivery',
      'instructions' => [
        'value' => 'Test instructions.',
        'format' => 'plain_text',
      ],
    ]);
    $this->paymentGateway->save();

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);

    $this->order = $this->createEntity('commerce_order', [
      'uid' => $this->loggedInUser->id(),
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);

    $this->paymentUri = Url::fromRoute('entity.commerce_payment.collection', ['commerce_order' => $this->order->id()])->toString();
  }

  /**
   * Tests creating a payment for an order.
   */
  public function testPaymentCreation() {
    $this->drupalGet($this->paymentUri . '/add');
    $this->assertSession()->pageTextContains('Manual example');
    $this->getSession()->getPage()->pressButton('Continue');
    $this->submitForm(['payment[amount][number]' => '100'], 'Add payment');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Pending');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::load(1);
    $this->assertEquals($payment->getOrderId(), $this->order->id());
    $this->assertEquals($payment->getAmount()->getNumber(), '100');
    $this->assertEquals($payment->getState()->getLabel(), 'Pending');

    $this->drupalGet($this->paymentUri . '/add');
    $this->assertSession()->pageTextContains('Manual example');
    $this->getSession()->getPage()->pressButton('Continue');
    $this->submitForm(['payment[amount][number]' => '100', 'payment[received]' => TRUE], 'Add payment');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Received');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::load(2);
    $this->assertEquals($payment->getOrderId(), $this->order->id());
    $this->assertEquals($payment->getAmount()->getNumber(), '100');
    $this->assertEquals($payment->getState()->getLabel(), 'Received');
  }

  /**
   * Tests receiving a payment after creation.
   */
  public function testPaymentReceive() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);
    $this->paymentGateway->getPlugin()->createPayment($payment);

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/receive');
    $this->submitForm(['payment[amount][number]' => '10'], 'Receive');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Pending');
    $this->assertSession()->pageTextContains('Received');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Received');
  }

  /**
   * Tests refunding a received payment.
   */
  public function testPaymentRefund() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);
    $this->paymentGateway->getPlugin()->createPayment($payment, TRUE);

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/refund');
    $this->submitForm(['payment[amount][number]' => '10'], 'Refund');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Received');
    $this->assertSession()->pageTextContains('Refunded');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Refunded');
  }

  /**
   * Tests voiding a pending payment.
   */
  public function testPaymentVoid() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);
    $this->paymentGateway->getPlugin()->createPayment($payment);

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/void');
    $this->getSession()->getPage()->pressButton('Void');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Voided');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Voided');
  }

}
