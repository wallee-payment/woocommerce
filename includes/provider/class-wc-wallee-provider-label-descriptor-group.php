<?php
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * Provider of label descriptor group information from the gateway.
 */
class WC_Wallee_Provider_Label_Descriptor_Group extends WC_Wallee_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_wallee_label_descriptor_group');
	}

	/**
	 * Returns the label descriptor group by the given code.
	 *
	 * @param int $id
	 * @return \Wallee\Sdk\Model\LabelDescriptorGroup
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of label descriptor groups.
	 *
	 * @return \Wallee\Sdk\Model\LabelDescriptorGroup[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
		$label_descriptor_group_service = new \Wallee\Sdk\Service\LabelDescriptorGroupService(WC_Wallee_Helper::instance()->get_api_client());
		return $label_descriptor_group_service->all();
	}

	protected function get_id($entry){
		/* @var \Wallee\Sdk\Model\LabelDescriptorGroup $entry */
		return $entry->getId();
	}
}