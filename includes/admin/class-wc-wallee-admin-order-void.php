<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * wallee WooCommerce
 *
 * This WooCommerce plugin enables to process payments with wallee (https://www.wallee.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * WC Wallee Admin Order Void class
 */
class WC_Wallee_Admin_Order_Void {

	public static function init(){
		add_action('woocommerce_order_item_add_line_buttons', array(
			__CLASS__,
			'render_execute_void_button' 
		));
		
		add_action('wp_ajax_woocommerce_wallee_execute_void', array(
			__CLASS__,
			'execute_void' 
		));
		
		add_action('wallee_five_minutes_cron', array(
			__CLASS__,
			'update_voids' 
		));
		
		add_action('wallee_update_running_jobs', array(
			__CLASS__,
			'update_for_order' 
		));
	}

	public static function render_execute_void_button(WC_Order $order){
		$gateway = wc_get_payment_gateway_by_order($order);
		if ($gateway instanceof WC_Wallee_Gateway) {
		    $transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
		    if ($transaction_info->get_state() == \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
				echo '<button type="button" class="button wallee-void-button action-wallee-void-cancel" style="display:none">' .
						 __('Cancel', 'woo-wallee') . '</button>';
				echo '<button type="button" class="button button-primary wallee-void-button action-wallee-void-execute" style="display:none">' .
						 __('Execute Void', 'woo-wallee') . '</button>';
				echo '<label for="restock_voided_items" style="display:none">' . __('Restock items', 'woo-wallee') . '</label>';
				echo '<input type="checkbox" id="restock_voided_items" name="restock_voided_items" checked="checked" style="display:none">';
			}
		}
	}

	public static function execute_void(){
		ob_start();
		
		global $wpdb;
		
		check_ajax_referer('order-item', 'security');
		
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}
		
		$order_id = absint($_POST['order_id']);
		$order = WC_Order_Factory::get_order($order_id);
		
		$restock_void_items = 'true' === $_POST['restock_voided_items'];
		$current_void_id = null;
		try {
			wc_transaction_query("start");
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order_id);
			if (!$transaction_info->get_id()) {
				throw new Exception(__('Could not load corresponding transaction'));
			}
			
			WC_Wallee_Helper::instance()->lock_by_transaction_id($transaction_info->get_space_id(), $transaction_info->get_transaction_id());
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_transaction($transaction_info->get_space_id(), 
					$transaction_info->get_transaction_id(), $transaction_info->get_space_id());
			
			if ($transaction_info->get_state() != \Wallee\Sdk\Model\TransactionState::AUTHORIZED) {
				throw new Exception(__('The transaction is not in a state to be voided.', 'woo-wallee'));
			}
			
			if (WC_Wallee_Entity_Void_Job::count_running_void_for_transaction($transaction_info->get_space_id(), 
					$transaction_info->get_transaction_id()) > 0) {
				throw new Exception(__('Please wait until the existing void is processed.', 'woo-wallee'));
			}
			if (WC_Wallee_Entity_Completion_Job::count_running_completion_for_transaction($transaction_info->get_space_id(), 
					$transaction_info->get_transaction_id()) > 0) {
				throw new Exception(__('There is a completion in process. The order can not be voided.', 'woo-wallee'));
			}
			
			$void_job = new WC_Wallee_Entity_Void_Job();
			$void_job->set_restock($restock_void_items);
			$void_job->set_space_id($transaction_info->get_space_id());
			$void_job->set_transaction_id($transaction_info->get_transaction_id());
			$void_job->set_state(WC_Wallee_Entity_Void_Job::STATE_CREATED);
			$void_job->set_order_id($order_id);
			$void_job->save();
			$current_void_id = $void_job->get_id();
			wc_transaction_query("commit");
		}
		catch (Exception $e) {
			wc_transaction_query("rollback");
			wp_send_json_error(array(
				'error' => $e->getMessage() 
			));
			return;
		}
		
		try {
			self::send_void($current_void_id);
			wp_send_json_success(
					array(
						'message' => __('The transaction is updated automatically once the result is available.', 'woo-wallee') 
					));
		}
		catch (Exception $e) {
			wp_send_json_error(array(
				'error' => $e->getMessage() 
			));
		}
	}

	protected static function send_void($void_job_id){
		global $wpdb;
		$void_job = WC_Wallee_Entity_Void_Job::load_by_id($void_job_id);
		wc_transaction_query("start");
		WC_Wallee_Helper::instance()->lock_by_transaction_id($void_job->get_space_id(), $void_job->get_transaction_id());
		//Reload void job;
		$void_job = WC_Wallee_Entity_Void_Job::load_by_id($void_job_id);
		if ($void_job->get_state() != WC_Wallee_Entity_Void_Job::STATE_CREATED) {
			//Already sent in the meantime
			wc_transaction_query("rollback");
			return;
		}
		try {
		    $void_service = new \Wallee\Sdk\Service\TransactionVoidService(WC_Wallee_Helper::instance()->get_api_client());
			
			$void = $void_service->voidOnline($void_job->get_space_id(), $void_job->get_transaction_id());
			$void_job->set_void_id($void->getId());
			$void_job->set_state(WC_Wallee_Entity_Void_Job::STATE_SENT);
			$void_job->save();
			wc_transaction_query("commit");
		}
    	catch (\Wallee\Sdk\ApiException $e) {
           if ($e->getResponseObject() instanceof \Wallee\Sdk\Model\ClientError) {
               $void_job->set_state(WC_Wallee_Entity_Void_Job::STATE_DONE);
               $void_job->save();
               wc_transaction_query("commit");
               
           }
           else{
               $void_job->save();
               wc_transaction_query("commit");
               WooCommerce_Wallee::instance()->log('Error sending void. '.$e->getMessage(), WC_Log_Levels::INFO);
               throw $e;
           }
    	}
		catch (Exception $e) {
			$void_job->save();
			wc_transaction_query("commit");
			WooCommerce_Wallee::instance()->log('Error sending void. '.$e->getMessage(), WC_Log_Levels::INFO);
			throw $e;
		}
	}

	public static function update_for_order(WC_Order $order){
	    $data = WC_Wallee_Helper::instance()->get_transaction_id_map_for_order($order);
	
		$void_job = WC_Wallee_Entity_Void_Job::load_running_void_for_transaction($data['space_id'], $data['transaction_id']);
		
		if ($void_job->get_state() == WC_Wallee_Entity_Void_Job::STATE_CREATED) {
			self::send_void($void_job->get_id());
		}
	}

	public static function update_voids(){
	    $to_process = WC_Wallee_Entity_Void_Job::load_not_sent_job_ids();
		foreach ($to_process as $id) {
			try {
				self::send_void($id);
			}
			catch (Exception $e) {
				$message = sprintf(__('Error updating void job with id %d: %s', 'woo-wallee'), $id, $e->getMessage());
				WooCommerce_Wallee::instance()->log($message, WC_Log_Levels::ERROR);
			}
		}
	}
}
WC_Wallee_Admin_Order_Void::init();
