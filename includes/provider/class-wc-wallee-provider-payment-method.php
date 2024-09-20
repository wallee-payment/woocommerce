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
 * Provider of payment method information from the gateway.
 */
class WC_Wallee_Provider_Payment_Method extends WC_Wallee_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_wallee_payment_methods' );
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id id.
	 * @return \Wallee\Sdk\Model\PaymentMethod
	 */
	public function find( $id ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $id );
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \Wallee\Sdk\Model\PaymentMethod[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\Wallee\Sdk\Model\PaymentMethod[]
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$method_service = new \Wallee\Sdk\Service\PaymentMethodService( WC_Wallee_Helper::instance()->get_api_client() );
		return $method_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \Wallee\Sdk\Model\PaymentMethod $entry */ //phpcs:ignore
		return $entry->getId();
	}
}
