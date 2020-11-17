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
 * This class handles the customer document downloads
 */
class WC_Wallee_Customer_Document {

	public static function init(){
		add_action('woocommerce_view_order', array(
			__CLASS__,
			'render_download_buttons' 
		), 20, 1);
		add_action('init', array(
			__CLASS__,
			'download_document' 
		));
	}

	public static function render_download_buttons($order_id){
		$order = WC_Order_Factory::get_order($order_id);
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_Wallee_Gateway)) {
			return;
		}
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
		if (is_null($transaction_info->get_id())) {
			return;
		}
		$packing = false;
		$invoice = false;
		if (get_option(WooCommerce_Wallee::CK_CUSTOMER_INVOICE) == 'yes' && in_array($transaction_info->get_state(),
				array(
				    \Wallee\Sdk\Model\TransactionState::COMPLETED,
				    \Wallee\Sdk\Model\TransactionState::FULFILL,
				    \Wallee\Sdk\Model\TransactionState::DECLINE 
				))) {
			$invoice = true;
		}
		if (get_option(WooCommerce_Wallee::CK_CUSTOMER_PACKING) == 'yes' && $transaction_info->get_state() == \Wallee\Sdk\Model\TransactionState::FULFILL) {
			$packing = true;
		}
		if ($invoice || $packing) {
			?>
<section class="woocommerce-order-wallee-documents">
	<h2><?php _e('Order Documents', 'woo-wallee');?></h2>
				 <?php if($invoice) :?>
					<span><a
		href="<?php
				echo add_query_arg(
						array(
							'wallee_action' => 'download_invoice',
							'post' => $order_id,
							'nonce' => wp_create_nonce('download_invoice') 
						));
				?>"
		class="woocommerce-order-wallee-download woocommerce-order-wallee-download-invoice"><?php _e('Download Invoice', 'woo-wallee')?></a></span>
				<?php endif;?>
				<?php if($packing) :?>
				<span><a
		href="<?php
				echo add_query_arg(
						array(
							'wallee_action' => 'download_packing',
							'post' => $order_id,
							'nonce' => wp_create_nonce('download_packing') 
						));
				?>"
		class="woocommerce-order-wallee-download woocommerce-order-wallee-download-packingslip"><?php _e('Download Packing Slip', 'woo-wallee')?></a></span>
				<?php endif;?>
				
</section>
<?php
		}
	}

	/**
	 * Check if request is PDF action.
	 *
	 * @return bool
	 */
	private static function is_pdf_request(){
		return (isset($_GET['post']) && isset($_GET['wallee_action']) && isset($_GET['nonce']));
	}

	/**
	 * Frontend pdf actions callback.
	 * Customers only have permission to view invoice, so invoice should be created by system/admin.
	 */
	public static function download_document(){
		if (!self::is_pdf_request()) {
			return;
		}
		
		// verify nonce.
		$action = sanitize_key($_GET['wallee_action']);
		
		$nonce = sanitize_key($_GET['nonce']);
		if (!wp_verify_nonce($nonce, $action)) {
			wp_die('Invalid request.');
		}
		
		if (!is_user_logged_in()) {
			wp_die('Access denied');
		}
		
		// verify woocommerce order.
		$post_id = intval($_GET['post']);
		$order = WC_Order_Factory::get_order($post_id);
		if (!$order) {
			wp_die('Order not found.');
		}
		
		// check if user has ordered order.
		$user = wp_get_current_user();
		$order_id = $order->get_id();
		$customer_user_id = $order->get_customer_id();
		if ($user->ID !== $customer_user_id) {
			wp_die('Access denied');
		}
		try {
			
			switch ($action) {
				case 'download_invoice':
				    if (get_option(WooCommerce_Wallee::CK_CUSTOMER_INVOICE) != 'yes') {
						wp_die('Access denied');
					}
					WC_Wallee_Download_Helper::download_invoice($order_id);
					break;
				case 'download_packing':
				    if (get_option(WooCommerce_Wallee::CK_CUSTOMER_PACKING) != 'yes') {
						wp_die('Access denied');
					}
					WC_Wallee_Download_Helper::download_packing_slip($order_id);
					break;
			}
		}
		catch (Exception $e) {
			wc_add_notice(__('There was an error downloading the document.', 'woo-wallee'), 'error');
		}
		wp_redirect(wc_get_endpoint_url('my-account/view-order', $order_id, wc_get_page_permalink('my-account')));
		exit();
	}
}
WC_Wallee_Customer_Document::init();
