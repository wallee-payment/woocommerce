<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Webhook processor to handle token version state transitions.
 */
class WC_Wallee_Webhook_Token_Version extends WC_Wallee_Webhook_Abstract {

	public function process(WC_Wallee_Webhook_Request $request){
		$token_service = WC_Wallee_Service_Token::instance();
		$token_service->update_token_version($request->get_space_id(), $request->get_entity_id());
	}
}