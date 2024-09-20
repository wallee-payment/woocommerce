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
 * Handles strategy for processing refund-related webhook requests.
 * This class extends the base webhook strategy to specifically manage webhook requests
 * that deal with refund transactions. This includes updating the status of refund jobs within the system,
 * processing related order modifications, and handling state transitions for refunds.
 */
class WC_Wallee_Webhook_Refund_Strategy extends WC_Wallee_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_Wallee_Service_Webhook::WALLEE_REFUND == $webhook_entity_id;
	}

	/**
	 * Load entity.
	 *
	 * @inheritDoc
	 * @param WC_Wallee_Webhook_Request $request The webhook request.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		$refund_service = new \Wallee\Sdk\Service\RefundService( WC_Wallee_Helper::instance()->get_api_client() );
		return $refund_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the order id.
	 *
	 * @inheritDoc
	 * @param \Wallee\Sdk\Model\Refund $object The refund object.
	 */
	protected function get_order_id( $object ) {
		return WC_Wallee_Entity_Transaction_Info::load_by_transaction(
			$object->getTransaction()->getLinkedSpaceId(),
			$object->getTransaction()->getId()
		)->get_order_id();
	}

	/**
	 * Processes the incoming webhook request related to refunds.
	 *
	 * This method retrieves the refund details from the API and updates the associated order
	 * based on the refund's state.
	 *
	 * @param WC_Wallee_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		/* @var \Wallee\Sdk\Model\Refund $refund */
		$refund = $this->load_entity( $request );
		$order = $this->get_order( $refund );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $refund, $request );
		}
	}

	/**
	 * Performs additional order-related processing based on the refund state.
	 *
	 * @param WC_Order $order The WooCommerce order associated with the refund.
	 * @param \Wallee\Sdk\Model\Refund $refund The transaction refund object.
		 * @param WC_Wallee_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, \Wallee\Sdk\Model\Refund $refund, WC_Wallee_Webhook_Request $request ) {
		/* @var \Wallee\Sdk\Model\Refund $refund */
		switch ( $request->get_state() ) {
			case \Wallee\Sdk\Model\RefundState::FAILED:
				// fallback.
				$this->failed( $refund, $order );
				break;
			case \Wallee\Sdk\Model\RefundState::SUCCESSFUL:
				$this->refunded( $refund, $order );
				// Nothing to do.
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Handles actions to be performed when a refund transaction fails.
	 *
	 * @param \Wallee\Sdk\Model\Refund $refund refund.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function failed( \Wallee\Sdk\Model\Refund $refund, WC_Order $order ) {
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_external_id( $refund->getLinkedSpaceId(), $refund->getExternalId() );
		if ( $refund_job->get_id() ) {
			$refund_job->set_state( WC_Wallee_Entity_Refund_Job::WALLEE_STATE_FAILURE );
			if ( $refund->getFailureReason() != null ) {
				$refund_job->set_failure_reason( $refund->getFailureReason()->getDescription() );
			}
			$refund_job->save();
			$refunds = $order->get_refunds();
			foreach ( $refunds as $wc_refund ) {
				if ( $wc_refund->get_meta( '_wallee_refund_job_id', true ) == $refund_job->get_id() ) {
					$wc_refund->set_status( 'failed' );
					$wc_refund->save();
					break;
				}
			}
		}
	}

	/**
	 * Handles actions to be performed when a refund transaction is successful.
	 *
	 * @param \Wallee\Sdk\Model\Refund $refund refund.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function refunded( \Wallee\Sdk\Model\Refund $refund, WC_Order $order ) {
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_external_id( $refund->getLinkedSpaceId(), $refund->getExternalId() );

		if ( $refund_job->get_id() ) {
			$refund_job->set_state( WC_Wallee_Entity_Refund_Job::WALLEE_STATE_SUCCESS );
			$refund_job->save();
			$refunds = $order->get_refunds();
			foreach ( $refunds as $wc_refund ) {
				if ( $wc_refund->get_meta( '_wallee_refund_job_id', true ) == $refund_job->get_id() ) {
					$wc_refund->set_status( 'completed' );
					$wc_refund->save();
					break;
				}
			}
		}
	}
}
