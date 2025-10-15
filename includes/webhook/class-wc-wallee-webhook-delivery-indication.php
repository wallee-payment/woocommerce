<?php
/**
 * Plugin Name: Wallee
 * Author: wallee AG
 * Text Domain: wallee
 * Domain Path: /languages/
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (https://www.wallee.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Webhook processor to handle delivery indication state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_Wallee_Webhook_Delivery_Indication_Strategy
 */
class WC_Wallee_Webhook_Delivery_Indication extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_Wallee_Webhook_Delivery_Indication_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_Wallee_Webhook_Delivery_Indication_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\DeliveryIndication DeliveryIndication.
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Delivery_Indication_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int|string
	 */
	protected function get_order_id( $delivery_indication ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Delivery_Indication_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $delivery_indication );
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int
	 */
	protected function get_transaction_id( $delivery_indication ) {
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		return $delivery_indication->getLinkedTransaction();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $delivery_indication delivery indication.
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $delivery_indication, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Delivery_Indication_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $delivery_indication, $request );
	}
}
