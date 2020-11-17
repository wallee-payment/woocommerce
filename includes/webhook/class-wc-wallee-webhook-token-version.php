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
 * Webhook processor to handle token version state transitions.
 */
class WC_Wallee_Webhook_Token_Version extends WC_Wallee_Webhook_Abstract {

    public function process(WC_Wallee_Webhook_Request $request){
        $token_service = WC_Wallee_Service_Token::instance();
		$token_service->update_token_version($request->get_space_id(), $request->get_entity_id());
	}
}