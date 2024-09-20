<?php
/**
 *
 * WC_Wallee_Webhook_Method_Configuration Class
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (https://www.wallee.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class WC_Wallee_Webhook_Method_Configuration extends WC_Wallee_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$payment_method_configuration_service = WC_Wallee_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}
