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
 * Provider of label descriptor information from the gateway.
 */
class WC_Wallee_Provider_Label_Description extends WC_Wallee_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_wallee_label_descriptions');
	}

	/**
	 * Returns the label descriptor by the given code.
	 *
	 * @param int $id
	 * @return \Wallee\Sdk\Model\LabelDescriptor
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of label descriptors.
	 *
	 * @return \Wallee\Sdk\Model\LabelDescriptor[]
	 */
	public function get_all(){		
		return parent::get_all();
	}

	protected function fetch_data(){
	    $label_description_service = new \Wallee\Sdk\Service\LabelDescriptionService(WC_Wallee_Helper::instance()->get_api_client());
		return $label_description_service->all();
	}

	protected function get_id($entry){
		/* @var \Wallee\Sdk\Model\LabelDescriptor $entry */
		return $entry->getId();
	}
}