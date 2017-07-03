<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Webhook processor to handle refund state transitions.
 */
class WC_Wallee_Webhook_Refund extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 *
	 * @see WC_Wallee_Webhook_Order_Related_Abstract::load_entity()
	 * @return \Wallee\Sdk\Model\Refund
	 */
	protected function load_entity(WC_Wallee_Webhook_Request $request){
		$refund_service = new \Wallee\Sdk\Service\RefundService(WC_Wallee_Helper::instance()->get_api_client());
		return $refund_service->read($request->get_space_id(), $request->get_entity_id());
	}

	protected function get_order_id($refund){
		/* @var \Wallee\Sdk\Model\Refund $refund */
		return $refund->getTransaction()->getMerchantReference();
	}

	protected function get_transaction_id($refund){
		/* @var \Wallee\Sdk\Model\Refund $refund */
		return $refund->getTransaction()->getId();
	}

	protected function process_order_related_inner(WC_Order $order, $refund){
		/* @var \Wallee\Sdk\Model\Refund $refund */
		switch ($refund->getState()) {
			case \Wallee\Sdk\Model\Refund::STATE_FAILED:
				$this->failed($refund, $order);
				break;
			case \Wallee\Sdk\Model\Refund::STATE_SUCCESSFUL:
				$this->refunded($refund, $order);
			default:
				// Nothing to do.
				break;
		}
	}

	protected function failed(\Wallee\Sdk\Model\Refund $refund, WC_Order $order){
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_external_id($refund->getLinkedSpaceId(), $refund->getExternalId());
		if ($refund_job->get_id()) {
			$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_FAILURE);
			if ($refund->getFailureReason() != null) {
				$refund_job->set_failure_reason($refund->getFailureReason()->getDescription(), $reasons);
			}
			$refund_job->save();
		}
	}

	protected function refunded(\Wallee\Sdk\Model\Refund $refund, WC_Order $order){
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_external_id($refund->getLinkedSpaceId(), $refund->getExternalId());
		
		if ($refund_job->get_id()) {
			$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_SUCCESS);
			
			$refund_job->save();
		}
	}
}