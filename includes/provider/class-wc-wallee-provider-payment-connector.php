<?php
if (!defined('ABSPATH')) {
    exit(); // Exit if accessed directly.
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
 * Provider of payment connector information from the gateway.
 */
class WC_Wallee_Provider_Payment_Connector extends WC_Wallee_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_wallee_payment_connectors');
	}

	/**
	 * Returns the payment connector by the given id.
	 *
	 * @param int $id
	 * @return \Wallee\Sdk\Model\PaymentConnector
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment connectors.
	 *
	 * @return \Wallee\Sdk\Model\PaymentConnector[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $connector_service = new \Wallee\Sdk\Service\PaymentConnectorService(WC_Wallee_Helper::instance()->get_api_client());
		return $connector_service->all();
	}

	protected function get_id($entry){
		/* @var \Wallee\Sdk\Model\PaymentConnector $entry */
		return $entry->getId();
	}
}