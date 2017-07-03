<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * WC Wallee Admin class
 */
class WC_Wallee_Admin_Notices {

	public static function init(){
		add_action('admin_notices', array(
			__CLASS__,
			'manual_tasks_notices' 
		));
	}

	public static function manual_tasks_notices(){
		$number_of_manual_tasks = WC_Wallee_Service_Manual_Task::instance()->get_number_of_manual_tasks();
		if ($number_of_manual_tasks == 0) {
			return;
		}
		$manual_taks_url = self::get_manual_tasks_url();
		require_once WC_WALLEE_ABSPATH.'/views/admin-notices/manual-tasks.php';
	}

	/**
	 * Returns the URL to check the open manual tasks.
	 *
	 * @return string
	 */
	protected static function get_manual_tasks_url(){
		$manual_task_url = WC_Wallee_Helper::instance()->get_base_gateway_url();
		$space_id = get_option('wc_wallee_space_id');
		if (!empty($space_id)) {
			$manual_task_url .= '/s/' . $space_id . '/manual-task/list';
		}
		
		return $manual_task_url;
	}

	public static function migration_failed_notices(){
		require_once WC_WALLEE_ABSPATH.'views/admin-notices/migration-failed.php';
	}
	
	public static function plugin_deactivated(){
		require_once WC_WALLEE_ABSPATH.'views/admin-notices/plugin-deactivated.php';
	}
}
WC_Wallee_Admin_Notices::init();