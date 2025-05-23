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
 * Class WC_Wallee_Unique_Id.
 * This class handles the webhooks of Wallee
 *
 * @class WC_Wallee_Unique_Id
 */
class WC_Wallee_Webhook_Handler {

	/**
	 * Initialise
	 */
	public static function init() {
		add_action(
			'woocommerce_api_wallee_webhook',
			array(
				__CLASS__,
				'process',
			)
		);
	}

	/**
	 * Handle webhook errors.
	 *
	 * @param mixed $errno error number.
	 * @param mixed $errstr error string.
	 * @param mixed $errfile error file.
	 * @param mixed $errline error line.
	 *
	 * @throws ErrorException ErrorException.
	 */
	public static function handle_webhook_errors( $errno, $errstr, $errfile, $errline ) {
		$fatal = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
		if ( $errno & $fatal ) {
			throw new ErrorException( esc_html( $errstr ), esc_html( $errno ), esc_html( E_ERROR ), esc_html( $errfile ), esc_html( $errline ) );
		}
		return false;
	}

	/**
	 * Processes incoming webhook calls.
	 * This method handles both signed and unsigned payloads by determining the presence of a digital signature.
	 * It sets an initial HTTP 500 status to indicate a failure if the process crashes unexpectedly.
	 */
	public static function process() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// We set the status to 500, so if we encounter a state where the process crashes the webhook is marked as failed.
		header( 'HTTP/1.1 500 Internal Server Error' );
		$raw_post_data = $wp_filesystem->get_contents( 'php://input' );
		$signature = isset( $_SERVER['HTTP_X_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_SIGNATURE'] ) ) : '';
		set_error_handler( array( __CLASS__, 'handle_webhook_errors' ) );
		try {
			$clean_data = wp_kses_post( wp_unslash( $raw_post_data ) );
			$request = new WC_Wallee_Webhook_Request( json_decode( $clean_data ) );
			$client = WC_Wallee_Helper::instance()->get_api_client();
			$webhook_service = WC_Wallee_Service_Webhook::instance();

			// Handling of payloads without a signature (legacy method).
			// TODO add config to disable strategy/use default webhooks
			// Deprecated since 3.0.12.
			if ( empty( $signature ) ) {
				$webhook_model = $webhook_service->get_webhook_entity_for_id( $request->get_listener_entity_id() );
				$webhook_handler_class_name = $webhook_model->get_handler_class_name();
				$webhook_handler = $webhook_handler_class_name::instance();
				$webhook_handler->process( $request );
			}

			// Handling of payloads with a valid signature.
			// This payload signed has the transaction state.
			if ( ! empty( $signature ) && $client->getWebhookEncryptionService()->isContentValid( $signature, $clean_data ) ) {
				WC_Wallee_Webhook_Strategy_Manager::instance()->process( $request );
			}

			header( 'HTTP/1.1 200 OK' );
		} catch ( Exception $e ) {
			WooCommerce_Wallee::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
			// phpcs:ignore
			echo esc_textarea( $e->getMessage() );
			exit();
		}
		exit();
	}
}
WC_Wallee_Webhook_Handler::init();
