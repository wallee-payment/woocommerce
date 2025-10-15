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
 * Webhook processor to handle token state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_Wallee_Webhook_Token_Strategy
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
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_Wallee_Webhook_Token_Strategy::process'
        );
		$strategy = new WC_Wallee_Webhook_Token_Strategy();
		$strategy->process( $request );
	}
}
