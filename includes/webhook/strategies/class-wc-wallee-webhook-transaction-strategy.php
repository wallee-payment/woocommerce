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
	 * @param \Wallee\Sdk\Model\Transaction $transaction The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$order = $this->get_order( $request );
		$entity = $this->load_entity( $request );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $entity );
			if ($request->get_state() === \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
				do_action( 'wallee_transaction_authorized_send_email', $order->get_id() );
			}
		}
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction transaction.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction ) {
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$transaction_state = $transaction->getState();
		if ( $transaction_state != $transaction_info->get_state() ) {
			switch ( $transaction_state ) {
				case \Wallee\Sdk\Model\TransactionState::CONFIRMED:
				case \Wallee\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $transaction, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $transaction, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $transaction, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::FAILED:
					$this->failed( $transaction, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $transaction, $order );
					$this->fulfill( $transaction, $order );
					WC_Wallee_Helper::set_virtual_zero_total_orders_to_complete( $order );
					WC_Wallee_Helper::update_order_status_for_preorder_if_needed( $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $transaction, $order );
					break;
				case \Wallee\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $transaction, $order );
					$this->waiting( $transaction, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_Wallee_Service_Transaction::instance()->update_transaction_info( $transaction, $order );
	}

	/**
	 * Confirm.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_confirmed', true ) && ! $order->get_meta( '_wallee_authorized', true ) ) {
			do_action( 'wc_wallee_confirmed', $transaction, $order );
			$order->add_meta_data( '_wallee_confirmed', 'true', true );
			$default_status = apply_filters( 'wc_wallee_confirmed_status', 'wallee-redirected', $order );
			apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::CONFIRMED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_authorized', true ) ) {
			do_action( 'wc_wallee_authorized', $transaction, $order );
			$order->add_meta_data( '_wallee_authorized', 'true', true );
			$default_status = apply_filters( 'wc_wallee_authorized_status', 'on-hold', $order );
			apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::AUTHORIZED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_manual_check', true ) ) {
			do_action( 'wc_wallee_completed', $transaction, $order );
			$default_status = apply_filters( 'wc_wallee_completed_status', 'processing', $order );
			apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::COMPLETED, $default_status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_wallee_declined', $transaction, $order );
		$default_status = apply_filters( 'wc_wallee_decline_status', 'cancelled', $order );
		apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::DECLINE, $default_status );
		WC_Wallee_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_wallee_failed', $transaction, $order );
		$valid_order_statuses = array(
			// Default pending status.
			'pending',
			// Custom order statuses mapped.
			apply_filters( 'wallee_wc_status_for_transaction', 'confirmed' ),
			apply_filters( 'wallee_wc_status_for_transaction', 'failed' )
		);
		if ( in_array( $order->get_status( 'edit' ), $valid_order_statuses ) ) {
			$default_status = apply_filters( 'wc_wallee_failed_status', 'failed', $order );
			apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::FAILED, $default_status, );
			WC_Wallee_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_wallee_fulfill', $transaction, $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $transaction->getId() );
	}

	/**
	 * Voided.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		$default_status = apply_filters( 'wc_wallee_voided_status', 'cancelled', $order );
		apply_filters( 'wallee_order_update_status', $order, \Wallee\Sdk\Model\TransactionState::VOIDED, $default_status );
		do_action( 'wc_wallee_voided', $transaction, $order );
	}
}
