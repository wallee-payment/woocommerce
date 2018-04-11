<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This class handles the webhooks of Wallee
 */
class WC_Wallee_Webhook_Handler {
	
	public static function init(){
		add_action('woocommerce_api_wallee_webhook', array(
			__CLASS__,
			'process' 
		));
	}
	
	public static function handle_webhook_errors($errno, $errstr, $errfile, $errline){
		$fatal = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
		if($errno & $fatal){
			throw new ErrorException($errstr, $errno, E_ERROR, $errfile, $errline);
		}
		return false;
		
	}

	/**
	 * Process the webhook call.
	 */
	public static function process(){
	    $webhook_service = WC_Wallee_Service_Webhook::instance();
		
		$requestBody = trim(file_get_contents("php://input"));
		set_error_handler(array(__CLASS__, 'handle_webhook_errors'));
		try{
		    $request = new WC_Wallee_Webhook_Request(json_decode($requestBody));
			$webhook_model = $webhook_service->get_webhook_entity_for_id($request->get_listener_entity_id());
			if ($webhook_model === null) {
			    WooCommerce_Wallee::instance()->log(sprintf('Could not retrieve webhook model for listener entity id: %s', $request->get_listener_entity_id()), WC_Log_Levels::ERROR);
				status_header(500);
				echo sprintf('Could not retrieve webhook model for listener entity id: %s', $request->get_listener_entity_id());
				exit();
				
			}
			$webhook_handler_class_name = $webhook_model->get_handler_class_name();
			$webhook_handler = $webhook_handler_class_name::instance();
			$webhook_handler->process($request);
		}
		catch(Exception $e){
		    WooCommerce_Wallee::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
			status_header(500);
			echo sprintf($e->getMessage());
			exit();
		}
		exit();
	}
}
WC_Wallee_Webhook_Handler::init();
