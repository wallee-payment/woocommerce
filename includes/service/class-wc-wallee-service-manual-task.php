<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This service provides methods to handle manual tasks.
 */
class WC_Wallee_Service_Manual_Task extends WC_Wallee_Service_Abstract {
	const CONFIG_KEY = 'wc_wallee_manual_task';

	/**
	 * Returns the number of open manual tasks.
	 *
	 * @return int
	 */
	public function get_number_of_manual_tasks(){
		return get_option(self::CONFIG_KEY, 0);
	}

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @return int
	 */
	public function update(){
		$number_of_manual_tasks = 0;
		$manual_task_service = new \Wallee\Sdk\Service\ManualTaskService(WC_Wallee_Helper::instance()->get_api_client());
		
		$space_id = get_option('wc_wallee_space_id');
		if (!empty($space_id)) {
			$number_of_manual_tasks = $manual_task_service->count($space_id, 
					$this->create_entity_filter('state', \Wallee\Sdk\Model\ManualTask::STATE_OPEN));
			update_option(self::CONFIG_KEY, $number_of_manual_tasks);
		}
		
		return $number_of_manual_tasks;
	}
}