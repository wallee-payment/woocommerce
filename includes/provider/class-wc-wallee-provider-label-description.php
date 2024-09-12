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
 * Provider of label descriptor information from the gateway.
 */
class WC_Wallee_Provider_Label_Description extends WC_Wallee_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_wallee_label_descriptions' );
	}

	/**
	 * Returns the label descriptor by the given code.
	 *
	 * @param int $id id.
	 * @return \Wallee\Sdk\Model\LabelDescriptor
	 */
	public function find( $id ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $id );
	}

	/**
	 * Returns a list of label descriptors.
	 *
	 * @return \Wallee\Sdk\Model\LabelDescriptor[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\Wallee\Sdk\Model\LabelDescriptor[]
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 * @throws \Wallee\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \Wallee\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$label_description_service = new \Wallee\Sdk\Service\LabelDescriptionService( WC_Wallee_Helper::instance()->get_api_client() );
		return $label_description_service->all();
	}

	/**
	 * Get Id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \Wallee\Sdk\Model\LabelDescriptor $entry */ //phpcs:ignore
		return $entry->getId();
	}
}
