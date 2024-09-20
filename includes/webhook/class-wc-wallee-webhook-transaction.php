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
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction ) {

		/* @var \Wallee\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $transaction->getState() != $transaction_info->get_state() ) {
			switch ( $transaction->getState() ) {
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
			$status = apply_filters( 'wc_wallee_confirmed_status', 'wallee-redirected', $order );
			$order->update_status( $status );
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
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( \Wallee\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_wallee_manual_check', true ) ) {
			do_action( 'wc_wallee_completed', $transaction, $order );
			$status = apply_filters( 'wc_wallee_completed_status', 'wallee-waiting', $order );
			$order->update_status( $status );
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
		$status = apply_filters( 'wc_wallee_decline_status', 'cancelled', $order );
		$order->update_status( $status );
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
		if ( $order->get_status( 'edit' ) == 'pending' || $order->get_status( 'edit' ) == 'wallee-redirected' ) {
			$status = apply_filters( 'wc_wallee_failed_status', 'failed', $order );
			$order->update_status( $status );
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
		$status = apply_filters( 'wc_wallee_voided_status', 'cancelled', $order );
		$order->update_status( $status );
		do_action( 'wc_wallee_voided', $transaction, $order );
	}
}
