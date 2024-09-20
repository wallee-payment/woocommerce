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
 * Class WC_Wallee_Webhook_Transaction_Strategy
 *
 * This class provides the implementation for processing transaction webhooks.
 * It includes methods for handling specific actions that need to be taken when
 * transaction-related webhook notifications are received, such as updating order
 * statuses, recording transaction logs, or triggering further business logic.
 */
class WC_Wallee_Webhook_Transaction_Strategy extends WC_Wallee_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_Wallee_Service_Webhook::WALLEE_TRANSACTION == $webhook_entity_id;
	}

	/**
	 * Process the webhook request.
	 *
	 * @param WC_Wallee_Webhook_Request $request The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$order = $this->get_order( $request );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $request );
		}
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, WC_Wallee_Webhook_Request $request ) {
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $request->get_state() != $transaction_info->get_state() ) {
			switch ( $request->get_state() ) {
				case \Wallee\Sdk\Model\TransactionState::CONFIRMED:
				case \Wallee\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::FAILED:
					$this->failed( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $request, $order );
					$this->fulfill( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $request, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $request, $order );
					$this->waiting( $request, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_Wallee_Service_Transaction::instance()->update_transaction_info( $this->load_entity( $request ), $order );
	}

	/**
	 * Confirm.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_confirmed', true ) && ! $order->get_meta( '_wallee_authorized', true ) ) {
			do_action( 'wc_wallee_confirmed', $this->load_entity( $request ), $order );
			$order->add_meta_data( '_wallee_confirmed', 'true', true );
			$status = apply_filters( 'wc_wallee_confirmed_status', 'wallee-redirected', $order );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_authorized', true ) ) {
			do_action( 'wc_wallee_authorized', $this->load_entity( $request ), $order );
			$status = apply_filters( 'wc_wallee_authorized_status', 'on-hold', $order );
			$order->add_meta_data( '_wallee_authorized', 'true', true );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_manual_check', true ) ) {
			do_action( 'wc_wallee_completed', $this->load_entity( $request ), $order );
			$status = apply_filters( 'wc_wallee_completed_status', 'processing', $order );
			$order->update_status( $status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_wallee_declined', $this->load_entity( $request ), $order );
		$status = apply_filters( 'wc_wallee_decline_status', 'cancelled', $order );
		$order->update_status( $status );
		WC_Wallee_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_wallee_failed', $this->load_entity( $request ), $order );
		if ( $order->get_status( 'edit' ) == 'pending' || $order->get_status( 'edit' ) == 'wallee-redirected' ) {
			$status = apply_filters( 'wc_wallee_failed_status', 'failed', $order );
			$order->update_status( $status );
			WC_Wallee_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_wallee_fulfill', $this->load_entity( $request ), $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $request->get_entity_id() );
	}

	/**
	 * Voided.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( WC_Wallee_Webhook_Request $request, WC_Order $order ) {
		$status = apply_filters( 'wc_wallee_voided_status', 'cancelled', $order );
		$order->update_status( $status );
		do_action( 'wc_wallee_voided', $this->load_entity( $request ), $order );
	}
}
