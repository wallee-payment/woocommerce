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
 * @see WC_Wallee_Webhook_Transaction_Invoice_Strategy
 */
class WC_Wallee_Webhook_Transaction_Invoice extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_Wallee_Webhook_Transaction_Invoice_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_Wallee_Webhook_Transaction_Invoice_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\TransactionInvoice
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Invoice_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Load transaction.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return \Wallee\Sdk\Model\Transaction
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_transaction( $transaction_invoice ) {
		/* @var \Wallee\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		$transaction_service = new \Wallee\Sdk\Service\TransactionService( WC_Wallee_Helper::instance()->get_api_client() );
		return $transaction_service->read( $transaction_invoice->getLinkedSpaceId(), $transaction_invoice->getCompletion()->getLineItemVersion()->getTransaction()->getId() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int|string
	 */
	protected function get_order_id( $transaction_invoice ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Invoice_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $transaction_invoice );
	}

	/**
	 * Get transaction invoice.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int
	 */
	protected function get_transaction_id( $transaction_invoice ) {
		/* @var \Wallee\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		return $transaction_invoice->getLinkedTransaction();
	}

	/**
	 * Process
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction_invoice transaction invoice.
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction_invoice, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Transaction_Invoice_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $transaction_invoice, $request );
	}
}
