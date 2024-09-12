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
 * This service provides methods to handle manual tasks.
 */
class WC_Wallee_Service_Manual_Task extends WC_Wallee_Service_Abstract {
	const WALLEE_CONFIG_KEY = 'wc_wallee_manual_task';

	/**
	 * Returns the number of open manual tasks.
	 *
	 * @return int
	 */
	public function get_number_of_manual_tasks() {
		return get_option( self::WALLEE_CONFIG_KEY, 0 );
	}

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @return int
	 */
	public function update() {
		$number_of_manual_tasks = 0;
		$manual_task_service = new \Wallee\Sdk\Service\ManualTaskService( WC_Wallee_Helper::instance()->get_api_client() );

		$space_id = get_option( WooCommerce_Wallee::WALLEE_CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$number_of_manual_tasks = $manual_task_service->count(
				$space_id,
				$this->create_entity_filter( 'state', \Wallee\Sdk\Model\ManualTaskState::OPEN )
			);
			update_option( self::WALLEE_CONFIG_KEY, $number_of_manual_tasks );
		}

		return $number_of_manual_tasks;
	}
}
