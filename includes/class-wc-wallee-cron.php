<?php
if (!defined('ABSPATH')) {
	exit();
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
 * This class handles the cron jobs
 */
class WC_Wallee_Cron {

	/**
	 * Hook in tabs.
	 */
	public static function init(){
		add_action('cron_schedules', array(
			__CLASS__,
			'add_custom_cron_schedule' 
		), 5);
	}

	public static function add_custom_cron_schedule($schedules){
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display' => __('Every Five Minutes') 
		);
		return $schedules;
	}

	public static function activate(){
		if (!wp_next_scheduled('wallee_five_minutes_cron')) {
			wp_schedule_event(time(), 'five_minutes', 'wallee_five_minutes_cron');
		}
	}

	public static function deactivate(){
		wp_clear_scheduled_hook('wallee_five_minutes_cron');
	}
}
WC_Wallee_Cron::init();