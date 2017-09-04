<?php
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * This service provides functions to deal with Wallee tokens.
 */
class WC_Wallee_Service_Token extends WC_Wallee_Service_Abstract {
	
	/**
	 * The token API service.
	 *
	 * @var \Wallee\Sdk\Service\TokenService
	 */
	private $token_service;
	
	/**
	 * The token version API service.
	 *
	 * @var \Wallee\Sdk\Service\TokenVersionService
	 */
	private $token_version_service;

	public function update_token_version($space_id, $token_version_id){
		$token_version = $this->get_token_version_service()->read($space_id, $token_version_id);
		$this->update_info($space_id, $token_version);
	}

	public function update_token($space_id, $token_id){
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType(\Wallee\Sdk\Model\EntityQueryFilter::TYPE_AND);
		$filter->setChildren(
				array(
					$this->create_entity_filter('token.id', $token_id),
					$this->create_entity_filter('state', \Wallee\Sdk\Model\TokenVersion::STATE_ACTIVE) 
				));
		$query->setFilter($filter);
		$query->setNumberOfEntities(1);
		$token_versions = $this->get_token_version_service()->search($space_id, $query);
		if (!empty($token_versions)) {
			$this->update_info($space_id, current($token_versions));
		}
		else {
			$info = WC_Wallee_Entity_Token_Info::load_by_token($space_id, $token_id);
			if ($info->get_id()) {
				$info->delete();
			}
		}
	}

	protected function update_info($space_id, \Wallee\Sdk\Model\TokenVersion $token_version){
		/* @var WC_Wallee_Entity_Token_Info $info */
		$info = WC_Wallee_Entity_Token_Info::load_by_token($space_id, $token_version->getToken()->getId());
		if (!in_array($token_version->getToken()->getState(), 
				array(
					\Wallee\Sdk\Model\Token::STATE_ACTIVE,
					\Wallee\Sdk\Model\Token::STATE_INACTIVE 
				))) {
			if ($info->get_id()) {
				$info->delete();
			}
			return;
		}
		
		$info->set_customer_id($token_version->getToken()->getCustomerId());
		$info->set_name($token_version->getName());
		
		/* @var Wallee_Payment_Model_Entity_PaymentMethodConfiguration $paymentMethod */
		
		$payment_method = WC_Wallee_Entity_Method_Configuration::load_by_configuration($space_id, 
				$token_version->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration()->getId());
		$info->set_payment_method_id($payment_method->get_id());
		$info->set_connector_id($token_version->getPaymentConnectorConfiguration()->getConnector());
		
		$info->set_space_id($space_id);
		$info->set_state($token_version->getToken()->getState());
		$info->set_token_id($token_version->getToken()->getId());
		$info->save();
	}

	public function delete_token($space_id, $token_id){
		$this->get_token_service()->delete($space_id, $token_id);
	}

	/**
	 * Returns the token API service.
	 *
	 * @return \Wallee\Sdk\Service\TokenService
	 */
	protected function get_token_service(){
		if ($this->token_service == null) {
			$this->token_service = new \Wallee\Sdk\Service\TokenService(WC_Wallee_Helper::instance()->get_api_client());
		}
		
		return $this->token_service;
	}

	/**
	 * Returns the token version API service.
	 *
	 * @return \Wallee\Sdk\Service\TokenVersionService
	 */
	protected function get_token_version_service(){
		if ($this->token_version_service == null) {
			$this->token_version_service = new \Wallee\Sdk\Service\TokenVersionService(WC_Wallee_Helper::instance()->get_api_client());
		}
		
		return $this->token_version_service;
	}
}