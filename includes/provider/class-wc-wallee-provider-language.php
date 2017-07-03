<?php
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * Provider of language information from the gateway.
 */
class WC_Wallee_Provider_Language extends WC_Wallee_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_wallee_languages');
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code
	 * @return \Wallee\Sdk\Model\RestLanguage
	 */
	public function find($code){
		return parent::find($code);
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code
	 * @return \Wallee\Sdk\Model\RestLanguage
	 */
	public function find_primary($code){
		$code = substr($code, 0, 2);
		foreach ($this->get_all() as $language) {
			if ($language->getIso2Code() == $code && $language->getPrimaryOfGroup()) {
				return $language;
			}
		}
		
		return false;
	}

	/**
	 * Returns a list of language.
	 *
	 * @return \Wallee\Sdk\Model\RestLanguage[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
		$language_service = new \Wallee\Sdk\Service\LanguageService(WC_Wallee_Helper::instance()->get_api_client());
		return $language_service->all();
	}

	protected function get_id($entry){
		/* @var \Wallee\Sdk\Model\RestLanguage $entry */
		return $entry->getIetfCode();
	}
}