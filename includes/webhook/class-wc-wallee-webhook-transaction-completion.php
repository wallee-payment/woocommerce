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
 * Webhook processor to handle transaction completion state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_Wallee_Webhook_Transaction_Completion_Strategy
 */
class WC_Wallee_Webhook_Transaction_Completion extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_Wallee_Webhook_Transaction_Completion_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_Wallee_Webhook_Transaction_Completion_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\TransactionCompletion TransactionCompletion.
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Completion_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $completion completion.
	 * @return int|string
	 */
	protected function get_order_id( $completion ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Completion_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $completion );
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $completion completion.
	 * @return int
	 */
	protected function get_transaction_id( $completion ) {
		/* @var \Wallee\Sdk\Model\TransactionCompletion $completion */ //phpcs:ignore
		return $completion->getLinkedTransaction();
	}

	/**
	 * Process order realted inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $completion completion.
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $completion, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Completion_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $completion, $request );
	}
}
