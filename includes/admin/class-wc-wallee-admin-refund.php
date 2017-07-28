<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * WC Wallee Admin class
 */
class WC_Wallee_Admin_Refund {
	private static $refundable_states = array(
		\Wallee\Sdk\Model\Transaction::STATE_COMPLETED,
		\Wallee\Sdk\Model\Transaction::STATE_DECLINE,
		\Wallee\Sdk\Model\Transaction::STATE_FULFILL 
	);

	public static function init(){
		add_action('woocommerce_order_item_add_action_buttons', array(
			__CLASS__,
			'render_refund_button_state' 
		), 1000);
		
		add_action('woocommerce_create_refund', array(
			__CLASS__,
			'store_refund_in_globals' 
		), 10, 2);
		add_action('wallee_five_minutes_cron', array(
			__CLASS__,
			'update_refunds' 
		));
		
		add_action('woocommerce_admin_order_items_after_refunds', array(
			__CLASS__,
			'render_refund_states' 
		), 1000, 1);
		
		add_action('wallee_update_running_jobs', array(
			__CLASS__,
			'update_for_order' 
		));
	}

	public static function render_refund_button_state(WC_Order $order){
		$gateway = wc_get_payment_gateway_by_order($order);
		if ($gateway instanceof WC_Wallee_Gateway) {
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
			if (!in_array($transaction_info->get_state(), self::$refundable_states)) {
				echo '<span id="wallee-remove-refund" style="dispaly:none;"></span>';
			}
			else {
				$existing_refund_job = WC_Wallee_Entity_Refund_Job::load_running_refund_for_transaction($transaction_info->get_space_id(), 
						$transaction_info->get_transaction_id());
				if ($existing_refund_job->get_id() > 0) {
					echo '<span class="wallee-action-in-progress">' . __('There is a refund in progress.', 'woocommerce-wallee') . '</span>';
					echo '<button type="button" class="button wallee-update-order">' . __('Update', 'woocommerce-wallee') . '</button>';
					echo '<span id="wallee-remove-refund" style="dispaly:none;"></span>';
				}
				echo '<span id="wallee-refund-restrictions" style="display:none;"></span>';
			}
		}
	}

	public static function render_refund_states($order_id){
		$refunds = WC_Wallee_Entity_Refund_Job::load_refunds_for_order($order_id);
		if (!empty($refunds)) {
			echo '<tr style="display:none"><td>';
			foreach ($refunds as $refund) {
				echo '<div class="wallee-refund-status" data-refund-id="' . $refund->get_wc_refund_id() . '" data-refund-state="' .
						 $refund->get_state() . '" ></div>';
			}
			echo '</td></tr>';
		}
	}

	public static function store_refund_in_globals($refund, $request_args){
		$GLOBALS['wallee_refund_id'] = $refund->get_id();
		$GLOBALS['wallee_refund_request_args'] = $request_args;
	}

	public static function execute_refund(WC_Order $order, WC_Order_Refund $refund){
		global $wpdb;
		$current_refund_job_id = null;
		$transaction_info = null;
		$refund_service = WC_Wallee_Service_Refund::instance();
		try {
			$wpdb->query("START TRANSACTION;");
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
			if (!$transaction_info->get_id()) {
				throw new Exception(__('Could not load corresponding wallee transaction', 'woocommerce-wallee'));
			}
			
			WC_Wallee_Helper::instance()->lock_by_transaction_id($transaction_info->get_space_id(), $transaction_info->get_transaction_id());
			
			if (WC_Wallee_Entity_Refund_Job::count_running_refund_for_transaction($transaction_info->get_space_id(), 
					$transaction_info->get_transaction_id()) > 0) {
				throw new Exception(__('Please wait until the pending refund is processed.', 'woocommerce-wallee'));
			}
			$wallee_refund = $refund_service->create($order, $refund);
			$refund_job = self::create_refund_job($order, $refund, $wallee_refund);
			$current_refund_job_id = $refund_job->get_id();
			
			$refund->add_meta_data('_wallee_refund_job_id', $refund_job->get_id());
			$refund->save();
			$wpdb->query("COMMIT;");
		}
		catch (Exception $e) {
			$wpdb->query("ROLLBACK;");
			throw $e;
		}
		self::send_refund($current_refund_job_id);
	}

	protected static function send_refund($refund_job_id){
		global $wpdb;
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_id($refund_job_id);
		$wpdb->query("START TRANSACTION;");
		WC_Wallee_Helper::instance()->lock_by_transaction_id($refund_job->get_space_id(), $refund_job->get_transaction_id());
		//Reload void job;
		$refund_job = WC_Wallee_Entity_Refund_Job::load_by_id($refund_job_id);
		
		if ($refund_job->get_state() != WC_Wallee_Entity_Refund_Job::STATE_CREATED) {
			//Already sent in the meantime
			$wpdb->query("ROLLBACK;");
			return;
		}
		try {
			$refund_service = WC_Wallee_Service_Refund::instance();
			$executed_refund = $refund_service->refund($refund_job->get_space_id(), $refund_job->get_refund());
			$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_SENT);
			
			if ($executed_refund->getState() == \Wallee\Sdk\Model\Refund::STATE_PENDING) {
				$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_PENDING);
			}
			$refund_job->save();
			$wpdb->query("COMMIT;");
		}
		catch (Exception $e) {
			$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_FAILURE);
			$refund_job->save();
			$wpdb->query("COMMIT;");
			throw new Exception(sprintf(__('There has been an error while sending the refund to the gateway. Error: %s', 'woocommerce-wallee'), $e->getMessage()));
		}
	}

	public static function update_for_order(WC_Order $order){
		$space_id = $order->get_meta('_wallee_linked_space_id', true);
		$transaction_id = $order->get_meta('_wallee_transaction_id', true);
		
		$refund_job = WC_Wallee_Entity_Refund_Job::load_running_refund_for_transaction($space_id, $transaction_id);
		
		if ($refund_job->get_state() == WC_Wallee_Entity_Refund_Job::STATE_CREATED) {
			self::send_refund($refund_job->get_id());
		}
	}

	public static function update_refunds(){
		$to_process = WC_Wallee_Entity_Refund_Job::load_not_sent_job_ids();
		foreach ($to_process as $id) {
			try {
				self::send_refund($id);
			}
			catch (Exception $e) {
				$message = sprintf(__('Error updating refund job wiht id %d: %s', 'woocommerce-wallee'), $id, $e->getMessage());
				WooCommerce_Wallee::instance()->log($message, WC_Log_Levels::ERROR);
			}
		}
	}

	/**
	 * Creates a new refund job for the given order and refund.
	 *
	 * @param WC_Order $order
	 * @param WC_Order_Refund $refund
	 * @param \Wallee\Sdk\Model\RefundCreate $wallee_refund
	 * @return WC_Wallee_Entity_Refund_Job
	 */
	private static function create_refund_job(WC_Order $order, WC_Order_Refund $refund, \Wallee\Sdk\Model\RefundCreate $wallee_refund){
		$refund_job = new WC_Wallee_Entity_Refund_Job();
		$refund_job->set_state(WC_Wallee_Entity_Refund_Job::STATE_CREATED);
		$refund_job->set_wc_refund_id($refund->get_id());
		$refund_job->set_order_id($order->get_id());
		$refund_job->set_space_id($wallee_refund->getTransaction()->getLinkedSpaceId());
		$refund_job->set_transaction_id($wallee_refund->getTransaction()->getId());
		$refund_job->set_external_id($wallee_refund->getExternalId());
		$refund_job->set_refund($wallee_refund);
		$refund_job->save();
		return $refund_job;
	}
}
WC_Wallee_Admin_Refund::init();
