<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This class handles the customer returns
 */
class WC_Wallee_Return_Handler {

	public static function init(){
		add_action('woocommerce_api_wallee_return', array(
			__CLASS__,
			'process' 
		));
	}

	public static function process(){
		if (isset($_GET['action']) && isset($_GET['order_key']) && isset($_GET['order_id'])) {
			$order_key = $_GET['order_key'];
			$order_id = absint($_GET['order_id']);
			$order = WC_Order_Factory::get_order($order_id);
			$action = $_GET['action'];
			if ($order->get_id() === $order_id && $order->get_order_key() === $order_key) {
				switch ($action) {
					case 'success':
						self::process_success($order);
						break;
					case 'failure':
						self::process_failure($order);
						break;
					default:
				}
			}
		}
		wp_redirect(home_url('/'));
		exit();
	}

	protected static function process_success(WC_Order $order){
	    $transaction_service = WC_Wallee_Service_Transaction::instance();
		
		$transaction_service->wait_for_transaction_state($order, 
				array(
				    \Wallee\Sdk\Model\TransactionState::CONFIRMED,
				    \Wallee\Sdk\Model\TransactionState::PENDING,
				    \Wallee\Sdk\Model\TransactionState::PROCESSING 
				), 5);
		$gateway = wc_get_payment_gateway_by_order($order);
		wp_redirect($gateway->get_return_url($order));
		exit();
	}

	protected static function process_failure(WC_Order $order){
	    $transaction_service = WC_Wallee_Service_Transaction::instance();
		$transaction_service->wait_for_transaction_state($order, array(
		    \Wallee\Sdk\Model\TransactionState::FAILED 
		), 5);
		$transaction = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
		
		$failure_reason = $transaction->get_failure_reason();
		if ($failure_reason !== null) {
		    WooCommerce_Wallee::instance()->add_notice($failure_reason, 'error');
		}
		wp_redirect(wc_get_checkout_url());
		exit();
	}
}
WC_Wallee_Return_Handler::init();
