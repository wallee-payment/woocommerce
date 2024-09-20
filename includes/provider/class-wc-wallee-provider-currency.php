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
 * Provider of currency information from the gateway.
 */
class WC_Wallee_Provider_Currency extends WC_Wallee_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_wallee_currencies' );
	}

	/**
	 * Returns the currency by the given code.
	 *
	 * @param string $code code.
	 * @return \Wallee\Sdk\Model\RestCurrency
	 */
	public function find( $code ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $code );
	}

	/**
	 * Returns a list of currencies.
	 *
	 * @return \Wallee\Sdk\Model\RestCurrency[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}


	/**
	 * Fetch data.
	 *
	 * @return array|\Wallee\Sdk\Model\RestCurrency[]
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$currency_service = new \Wallee\Sdk\Service\CurrencyService( WC_Wallee_Helper::instance()->get_api_client() );
		return $currency_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \Wallee\Sdk\Model\RestCurrency $entry */ //phpcs:ignore
		return $entry->getCurrencyCode();
	}
}
