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
 * Provider of language information from the gateway.
 */
class WC_Wallee_Provider_Language extends WC_Wallee_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_wallee_languages' );
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code code.
	 * @return \Wallee\Sdk\Model\RestLanguage
	 */
	public function find( $code ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $code );
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code code.
	 * @return \Wallee\Sdk\Model\RestLanguage
	 */
	public function find_primary( $code ) {
		$code = substr( $code, 0, 2 );
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $code && $language->getPrimaryOfGroup() ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Find by iso code.
	 *
	 * @param mixed $iso iso.
	 * @return false|\Wallee\Sdk\Model\RestLanguage
	 */
	public function find_by_iso_code( $iso ) {
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $iso || $language->getIso3Code() == $iso ) {
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
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\Wallee\Sdk\Model\RestLanguage[]
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$language_service = new \Wallee\Sdk\Service\LanguageService( WC_Wallee_Helper::instance()->get_api_client() );
		return $language_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \Wallee\Sdk\Model\RestLanguage $entry */ //phpcs:ignore
		return $entry->getIetfCode();
	}
}
