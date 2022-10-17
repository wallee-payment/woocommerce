<?php
/**
 *
 * WC_Wallee_Webhook_Manual_Task Class
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (http://www.wallee.com/)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Webhook processor to handle manual task state transitions.
 */
class WC_Wallee_Webhook_Manual_Task extends WC_Wallee_Webhook_Abstract {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$manual_task_service = WC_Wallee_Service_Manual_Task::instance();
		$manual_task_service->update();
	}
}
