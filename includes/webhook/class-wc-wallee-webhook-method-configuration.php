<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class WC_Wallee_Webhook_Method_Configuration extends WC_Wallee_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_Wallee_Webhook_Request $request
	 */
	public function process(WC_Wallee_Webhook_Request $request){
		$payment_method_configuration_service = WC_Wallee_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}