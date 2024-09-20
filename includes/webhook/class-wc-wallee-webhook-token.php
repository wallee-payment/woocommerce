<?php
/**
 *
 * WC_Wallee_Webhook_Token Class
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
 * Webhook processor to handle token state transitions.
 */
class WC_Wallee_Webhook_Token extends WC_Wallee_Webhook_Abstract {

	/**
	 * Process.
	 *
	 * @param WC_Wallee_Webhook_Request $request request.
	 * @return void
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	public function process( WC_Wallee_Webhook_Request $request ) {
		$token_service = WC_Wallee_Service_Token::instance();
		$token_service->update_token( $request->get_space_id(), $request->get_entity_id() );
	}
}
