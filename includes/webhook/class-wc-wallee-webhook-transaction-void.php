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
 * Webhook processor to handle transaction void state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_Wallee_Webhook_Transaction_Void_Strategy
 */
class WC_Wallee_Webhook_Transaction_Void extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 * Load entity.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return object|\Wallee\Sdk\Model\TransactionVoid
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_Wallee_Webhook_Request $request ) {
		$void_service = new \Wallee\Sdk\Service\TransactionVoidService( WC_Wallee_Helper::instance()->get_api_client() );
		return $void_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $void_transaction void transaction.
	 * @return int|string
	 */
	protected function get_order_id( $void_transaction ) {
		/* @var \Wallee\Sdk\Model\TransactionVoid $void */ //phpcs:ignore
		return WC_Wallee_Entity_Transaction_Info::load_by_transaction( $void_transaction->getTransaction()->getLinkedSpaceId(), $void_transaction->getTransaction()->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $void_transaction void transaction.
	 * @return int
	 */
	protected function get_transaction_id( $void_transaction ) {
		/* @var \Wallee\Sdk\Model\TransactionVoid $void_transaction */ //phpcs:ignore
		return $void_transaction->getLinkedTransaction();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $void_transaction void transaction.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $void_transaction ) {
		/* @var \Wallee\Sdk\Model\TransactionVoid $void_transaction */ //phpcs:ignore
		switch ( $void_transaction->getState() ) {
			case \Wallee\Sdk\Model\TransactionVoidState::FAILED:
				$this->failed( $void_transaction, $order );
				break;
			case \Wallee\Sdk\Model\TransactionVoidState::SUCCESSFUL:
				$this->success( $void_transaction, $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Success.
	 *
	 * @param \Wallee\Sdk\Model\TransactionVoid $void_transaction void.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function success( \Wallee\Sdk\Model\TransactionVoid $void_transaction, WC_Order $order ) {
		$void_job = WC_Wallee_Entity_Void_Job::load_by_void( $void_transaction->getLinkedSpaceId(), $void_transaction->getId() );
		if ( ! $void_job->get_id() ) {
			// We have no void job with this id -> the server could not store the id of the void after sending the request. (e.g. connection issue or crash).
			// We only have on running void which was not yet processed successfully and use it as it should be the one the webhook is for.
			$void_job = WC_Wallee_Entity_Void_Job::load_running_void_for_transaction( $void_transaction->getLinkedSpaceId(), $void_transaction->getLinkedTransaction() );
			if ( ! $void_job->get_id() ) {
				// void not initiated in shop backend ignore.
				return;
			}
			$void_job->set_void_id( $void_transaction->getId() );
		}
		$void_job->set_state( WC_Wallee_Entity_Void_Job::WALLEE_STATE_DONE );

		if ( $void_job->get_restock() ) {
			WC_Wallee_Helper::instance()->maybe_restock_items_for_order( $order );
		}
		$void_job->save();
	}

	/**
	 * Failed.
	 *
	 * @param \Wallee\Sdk\Model\TransactionVoid $void_transaction void.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function failed( \Wallee\Sdk\Model\TransactionVoid $void_transaction, WC_Order $order ) {
		$void_job = WC_Wallee_Entity_Void_Job::load_by_void( $void_transaction->getLinkedSpaceId(), $void_transaction->getId() );
		if ( ! $void_job->get_id() ) {
			// We have no void job with this id -> the server could not store the id of the void after sending the request. (e.g. connection issue or crash)
			// We only have on running void which was not yet processed successfully and use it as it should be the one the webhook is for.
			$void_job = WC_Wallee_Entity_Void_Job::load_running_void_for_transaction( $void_transaction->getLinkedSpaceId(), $void_transaction->getLinkedTransaction() );
			if ( ! $void_job->get_id() ) {
				// void not initiated in shop backend ignore.
				return;
			}
			$void_job->set_void_id( $void_transaction->getId() );
		}
		if ( $void_job->getFailureReason() != null ) {
			$void_job->set_failure_reason( $void_transaction->getFailureReason()->getDescription() );
		}
		$void_job->set_state( WC_Wallee_Entity_Void_Job::WALLEE_STATE_DONE );
		$void_job->save();
	}
}
