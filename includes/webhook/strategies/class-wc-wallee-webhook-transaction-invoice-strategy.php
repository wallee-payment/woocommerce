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
 * Class WC_Wallee_Webhook_Refund_Strategy
 *
 * Handles strategy for processing transaction invoice-related webhook requests.
 * This class extends the base webhook strategy to manage webhook requests specifically
 * dealing with transaction invoices. It focuses on updating order states based on the invoice details
 * retrieved from the webhook data.
 */
class WC_Wallee_Webhook_Transaction_Invoice_Strategy extends WC_Wallee_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_Wallee_Service_Webhook::WALLEE_TRANSACTION_INVOICE == $webhook_entity_id;
	}

	/**
	 * Load the entity.
	 *
	 * @inheritDoc
	 * @param WC_Wallee_Webhook_Request $request webhook request.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		$transaction_invoice_service = new \Wallee\Sdk\Service\TransactionInvoiceService( WC_Wallee_Helper::instance()->get_api_client() );
		return $transaction_invoice_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the order ID from the object.
	 *
	 * @inheritDoc
	 * @param object $object transaction entity object.
	 */
	protected function get_order_id( $object ) {
		/* @var \Wallee\Sdk\Model\TransactionInvoice $object */
		return WC_Wallee_Entity_Transaction_Info::load_by_transaction(
			$object->getLinkedSpaceId(),
			$object->getCompletion()->getLineItemVersion()->getTransaction()->getId()
		)->get_order_id();
	}

	/**
	 * Processes the incoming webhook request pertaining to transaction invoices.
	 *
	 * This method retrieves the transaction invoice details from the API and updates the associated
	 * WooCommerce order based on the state of the invoice.
	 *
	 * @param WC_Wallee_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		/* @var \Wallee\Sdk\Model\TransactionInvoice $transaction_invoice */
		$transaction_invoice = $this->load_entity( $request );
		$order = $this->get_order( $transaction_invoice );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $transaction_invoice, $request );
		}
	}

	/**
	 * Additional processing on the order based on the state of the transaction invoice.
	 *
	 * @param WC_Order $order The WooCommerce order linked to the invoice.
	 * @param \Wallee\Sdk\Model\TransactionInvoice $transaction_invoice The transaction invoice object.
	 * @param WC_Wallee_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, \Wallee\Sdk\Model\TransactionInvoice $transaction_invoice, WC_Wallee_Webhook_Request $request ) {
		switch ( $request->get_state() ) {
			case \Wallee\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
				$order->add_order_note( __( 'Invoice Not Settled' ) );
				break;
			case \Wallee\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
			case \Wallee\Sdk\Model\TransactionInvoiceState::PAID:
				$order->add_order_note( __( 'Invoice Settled' ) );
				break;
			default:
				// Nothing to do.
				break;
		}
	}
}
