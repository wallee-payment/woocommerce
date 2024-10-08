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
 * Class WC_Wallee_Webhook_Token_Strategy
 *
 * Handles the strategy for processing webhook requests related to tokens.
 * This class extends the base webhook strategy class and is specialized in handling
 * webhook requests that are associated with token updates. Tokens typically represent
 * authentication or authorization tokens used within the system.
 */
class WC_Wallee_Webhook_Token_Strategy extends WC_Wallee_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_Wallee_Service_Webhook::WALLEE_TOKEN == $webhook_entity_id;
	}

	/**
	 * Processes the incoming webhook request that pertains to tokens.
	 *
	 * This method invokes the token service to update the token identified by the
	 * space ID and entity ID provided in the webhook request. It ensures that token
	 * data is synchronized and up-to-date across the system.
	 *
	 * @param WC_Wallee_Webhook_Request $request The webhook request.
	 * @return void
	 * @throws Exception Throws an exception if there is an issue while processing the token update.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$token_service = WC_Wallee_Service_Token::instance();
		$token_service->update_token( $request->get_space_id(), $request->get_entity_id() );
	}
}
