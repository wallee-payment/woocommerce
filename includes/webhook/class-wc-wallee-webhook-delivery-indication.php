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
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\DeliveryIndication DeliveryIndication.
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		$delivery_indication_service = new \Wallee\Sdk\Service\DeliveryIndicationService( WC_Wallee_Helper::instance()->get_api_client() );
		return $delivery_indication_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int|string
	 */
	protected function get_order_id( $delivery_indication ) {
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		return WC_Wallee_Entity_Transaction_Info::load_by_transaction( $delivery_indication->getTransaction()->getLinkedSpaceId(), $delivery_indication->getTransaction()->getId() )->get_order_id();
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
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $delivery_indication ) {
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		switch ( $delivery_indication->getState() ) {
			case \Wallee\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review( $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Review.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function review( WC_Order $order ) {
		$status = apply_filters( 'wc_wallee_manual_task_status', 'wallee-manual', $order );
		$order->add_meta_data( '_wallee_manual_check', true );
		$order->update_status( $status, esc_html__( 'A manual decision about whether to accept the payment is required.', 'woo-wallee' ) );
	}
}
