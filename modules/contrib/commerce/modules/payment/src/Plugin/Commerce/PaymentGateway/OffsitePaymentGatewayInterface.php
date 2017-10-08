<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the base interface for off-site payment gateways.
 *
 * Off-site payment flow:
 * 1) Customer hits the "payment" checkout step.
 * 2) The PaymentProcess checkout pane shows the "offsite-payment" plugin form.
 * 3) The plugin form performs a redirect or shows an iFrame.
 * 4) The customer provides their payment details to the payment provider.
 * 5) The payment provider redirects the customer back to the return url.
 * 6) A payment is created in either onReturn() or onNotify().
 *
 * If the payment provider supports asynchronous notifications (IPNs), then
 * creating the payment in onNotify() is preferred, since it is guaranteed to
 * be called even if the customer does not return to the site.
 *
 * If the customer declines to provide their payment details, and cancels
 * the payment at the payment provider, they will be redirected back to the
 * cancel url.
 */
interface OffsitePaymentGatewayInterface extends PaymentGatewayInterface {

  /**
   * Gets the URL to the "notify" page.
   *
   * When supported, this page is called asynchronously to notify the site of
   * payment changes (new payment or capture/void/refund of an existing one).
   *
   * @return \Drupal\Core\Url
   *   The "notify" page url.
   */
  public function getNotifyUrl();

  /**
   * Processes the "return" request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the request is invalid or the payment failed.
   */
  public function onReturn(OrderInterface $order, Request $request);

  /**
   * Processes the "cancel" request.
   *
   * Allows the payment gateway to clean up any data added to the $order, set
   * a message for the customer.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function onCancel(OrderInterface $order, Request $request);

  /**
   * Processes the "notify" request.
   *
   * Note:
   * This method can't throw exceptions on failure because some payment
   * providers expect an error response to be returned in that case.
   * Therefore, the method can log the error itself and then choose which
   * response to return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response, or NULL to return an empty HTTP 200 response.
   */
  public function onNotify(Request $request);

}
