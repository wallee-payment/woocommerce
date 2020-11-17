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
 * Webhook processor to handle manual task state transitions.
 */
class WC_Wallee_Webhook_Manual_Task extends WC_Wallee_Webhook_Abstract {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param WC_Wallee_Webhook_Request $request
	 */
    public function process(WC_Wallee_Webhook_Request $request){
        $manual_task_service = WC_Wallee_Service_Manual_Task::instance();
		$manual_task_service->update();
	}
}