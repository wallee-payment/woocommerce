<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * wallee WooCommerce
 *
 * This WooCommerce plugin enables to process payments with wallee (https://www.wallee.com).
 *
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * Webhook processor to handle delivery indication state transitions.
 */
class WC_Wallee_Webhook_Delivery_Indication extends WC_Wallee_Webhook_Order_Related_Abstract {

	/**
	 *
	 * @see WC_Wallee_Webhook_Order_Related_Abstract::load_entity()
	 * @return \Wallee\Sdk\Model\DeliveryIndication
	 */
    protected function load_entity(WC_Wallee_Webhook_Request $request){
        $delivery_indication_service = new \Wallee\Sdk\Service\DeliveryIndicationService(WC_Wallee_Helper::instance()->get_api_client());
		return $delivery_indication_service->read($request->get_space_id(), $request->get_entity_id());
	}

	protected function get_order_id($delivery_indication){
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */
        return WC_Wallee_Entity_Transaction_Info::load_by_transaction($delivery_indication->getTransaction()->getLinkedSpaceId(), $delivery_indication->getTransaction()->getId())->get_order_id();
	}

	protected function get_transaction_id($delivery_indication){
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */
		return $delivery_indication->getLinkedTransaction();
	}

	protected function process_order_related_inner(WC_Order $order, $delivery_indication){
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */
		switch ($delivery_indication->getState()) {
		    case \Wallee\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review($order);
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	protected function review(WC_Order $order){
		$status = apply_filters('wc_wallee_manual_task_status', 'wallee-manual', $order);
		$order->add_meta_data('_wallee_manual_check', true);
		$order->update_status($status, __('A manual decision about whether to accept the payment is required.', 'woo-wallee'));
	}
}