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
 * Class WC_Wallee_Cron.
 * This class handles the cron jobs
 *
 * @class WC_Wallee_Cron
 */
class WC_Wallee_Cron {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action(
			'cron_schedules',
			array(
				__CLASS__,
				'add_custom_cron_schedule',
			),
			5
		);
	}

	/**
	 * Add cron schedule.
	 *
	 * @param  array $schedules schedules.
	 * @return array
	 */
	public static function add_custom_cron_schedule( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'Every Five Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Activate the cron.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'wallee_five_minutes_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'wallee_five_minutes_cron' );
		}
	}

	/**
	 * Deactivate the cron.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wallee_five_minutes_cron' );
	}
}
WC_Wallee_Cron::init();
