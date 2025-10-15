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
 * Webhook processor to handle transaction state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_Wallee_Webhook_Transaction_Strategy
 */
class WC_Wallee_Webhook_Transaction extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_Wallee_Webhook_Transaction_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_Wallee_Webhook_Transaction_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\Transaction
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		$transaction_service = new \Wallee\Sdk\Service\TransactionService( WC_Wallee_Helper::instance()->get_api_client() );
		return $transaction_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int|string
	 */
	protected function get_order_id( $transaction ) {
		/* @var \Wallee\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		return WC_Wallee_Entity_Transaction_Info::load_by_transaction( $transaction->getLinkedSpaceId(), $transaction->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int
	 */
	protected function get_transaction_id( $transaction ) {
		/* @var \Wallee\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		return $transaction->getId();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction transaction.
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $transaction );
	}
}
