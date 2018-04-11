<?php
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * Provider of payment method information from the gateway.
 */
class WC_Wallee_Provider_Payment_Method extends WC_Wallee_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_wallee_payment_methods');
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id
	 * @return \Wallee\Sdk\Model\PaymentMethod
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \Wallee\Sdk\Model\PaymentMethod[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $method_service = new \Wallee\Sdk\Service\PaymentMethodService(WC_Wallee_Helper::instance()->get_api_client());
		return $method_service->all();
	}

	protected function get_id($entry){
		/* @var \Wallee\Sdk\Model\PaymentMethod $entry */
		return $entry->getId();
	}
}