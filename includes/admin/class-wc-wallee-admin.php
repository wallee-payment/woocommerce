<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * WC Wallee Admin class
 */
class WC_Wallee_Admin {
	
	/**
	 * The single instance of the class.
	 *
	 * @var WC_Wallee_Admin
	 */
	protected static $_instance = null;

	/**
	 * Main WooCommerce Wallee Admin Instance.
	 *
	 * Ensures only one instance of WC Wallee Admin is loaded or can be loaded.
	 *
	 * @return WC_Wallee_Admin - Main instance.
	 */
	public static function instance(){
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WC Wallee Admin Constructor.
	 */
	protected function __construct(){
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes(){
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-document.php');
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-transaction.php');
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-notices.php');
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-order-completion.php');
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-order-void.php');
		require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-refund.php');
	}

	private function init_hooks(){
		add_action('plugins_loaded', array(
			$this,
			'loaded' 
		), 0);
		
		add_filter('woocommerce_get_settings_pages', array(
			$this,
			'add_settings' 
		));
		
		add_filter('plugin_action_links_' . WC_WALLEE_PLUGIN_BASENAME, array(
			$this,
			'plugin_action_links' 
		));
		
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		
		add_action('wc_wallee_settings_changed', array(
			WC_Wallee_Service_Method_Configuration::instance(),
			'synchronize' 
		));
		add_action('wc_wallee_settings_changed', array(
			WC_Wallee_Service_Webhook::instance(),
			'install' 
		));
		add_action('wc_wallee_settings_changed', array(
			WC_Wallee_Service_Manual_Task::instance(),
			'update' 
		));
		add_filter('woocommerce_hidden_order_itemmeta', array(
			$this,
			'hide_wallee_unique_id_meta' 
		), 10, 1);
		
		add_action('woocommerce_order_item_add_action_buttons', array(
			$this,
			'render_authorized_action_buttons' 
		), 1);
		
		add_action('wp_ajax_woocommerce_wallee_update_order', array(
			$this,
			'update_order' 
		));
		add_action('admin_init', array(
			$this,
			'handle_woocommerce_active'
		));
		
		add_action('woocommerce_admin_order_actions', array(
			$this,
			'remove_not_wanted_order_actions'
		), 10, 2);
	}
	
	public function handle_woocommerce_active(){
		// WooCommerce plugin not activated
		if (!is_plugin_active('woocommerce/woocommerce.php'))
		{
			// Deactivate myself
			deactivate_plugins(WC_WALLEE_PLUGIN_BASENAME);
			add_action('admin_notices', array(
				'WC_Wallee_Admin_Notices',
				'plugin_deactivated'
			));
		}
	}
	
	public function render_authorized_action_buttons(WC_Order $order){
		$gateway = wc_get_payment_gateway_by_order($order);
		if ($gateway instanceof WC_Wallee_Gateway) {
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
			if ($transaction_info->get_state() == \Wallee\Sdk\Model\Transaction::STATE_AUTHORIZED) {
				if (WC_Wallee_Entity_Completion_Job::count_running_completion_for_transaction($transaction_info->get_space_id(), 
						$transaction_info->get_transaction_id()) > 0 || WC_Wallee_Entity_Void_Job::count_running_void_for_transaction(
						$transaction_info->get_space_id(), $transaction_info->get_transaction_id()) > 0) {
					echo '<span class="wallee-action-in-progress">' . __('There is a completion/void in progress.', 'woocommerce-wallee') . '</span>';
					echo '<button type="button" class="button wallee-update-order">' . __('Update', 'woocommerce-wallee') . '</button>';
				}
				else {
					echo '<button type="button" class="button wallee-void-show">' . __('Void', 'woocommerce-wallee') . '</button>';
					echo '<button type="button" class="button button-primary wallee-completion-show">' . __('Completion', 'woocommerce-wallee') .
							 '</button>';
				}
			}
		}
	}
	
	public function remove_not_wanted_order_actions(array $actions, WC_Order $order){
		$gateway = wc_get_payment_gateway_by_order($order);
		if ($gateway instanceof WC_Wallee_Gateway) {
			if($order->has_status('on-hold')){
				unset($actions['processing']);
				unset($actions['complete']);
			}
		}
		return $actions;
	}

	/**
	 * Init WooCommerce Wallee when plugins are loaded. 
	 */
	public function loaded(){
		add_action('admin_init', array(
			$this,
			'enque_script_and_css' 
		));
	}

	public function enque_script_and_css(){
		wp_enqueue_style('woocommerce-wallee-admin-styles', WooCommerce_Wallee::instance()->plugin_url() . '/assets/css/admin.css');
		wp_enqueue_style('woocommerce-wallee-management-styles', WooCommerce_Wallee::instance()->plugin_url() . '/assets/css/management.css');
		wp_enqueue_script('wallee-admin-js', WooCommerce_Wallee::instance()->plugin_url() . '/assets/js/admin/management.js', 
				array(
					'jquery',
					'wc-admin-meta-boxes' 
				), null, true);
		
		$localize = array(
			'i18n_do_void' => __('Are you sure you wish to process this void? This action cannot be undone.', 'woocommerce-wallee'),
			'i18n_do_completion' => __('Are you sure you wish to process this completion? This action cannot be undone.', 'woocommerce-wallee') 
		);
		wp_localize_script('wallee-admin-js', 'wallee_admin_js_params', $localize);
	}

	public function hide_wallee_unique_id_meta($arr){
		$arr[] = '_wallee_unique_line_item_id';
		return $arr;
	}

	public function update_order(){
		ob_start();
		
		check_ajax_referer('order-item', 'security');
		
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}
		
		$order_id = absint($_POST['order_id']);
		$order = WC_Order_Factory::get_order($order_id);
		try {
			do_action('wallee_update_running_jobs', $order);
		}
		catch (Exception $e) {
			wp_send_json_error(array(
				'error' => $e->getMessage() 
			));
		}
		wp_send_json_success();
	}

	/**
	 * Add WooCommerce Wallee Settings Tab
	 *
	 * @param array $integrations
	 * @return array
	 */
	public function add_settings($integrations){
		$integrations[] = new WC_Wallee_Admin_Settings_Page();
		return $integrations;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param	mixed $links Plugin Action links
	 * @return	array
	 */
	public function plugin_action_links($links){
		$action_links = array(
			'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=wallee') . '" aria-label="' .
					 esc_attr__('View WC Wallee settings', 'woocommerce-wallee') . '">' . esc_html__('Settings', 'woocommerce-wallee') . '</a>',
		);
		
		return array_merge($action_links, $links);
	}
	
	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( WC_WALLEE_PLUGIN_BASENAME == $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'wc_wallee_docs_url', 'https://github.com/wallee-payment/woocommerce/wiki' ) ) . '" target="_blank" aria-label="' . esc_attr__( 'View Docs', 'woocommerce-wallee' ) . '">' . esc_html__( 'Docs', 'woocommerce-wallee' ) . '</a>',
				'source_code'    => '<a href="' . esc_url( apply_filters( 'wc_wallee_source_url', 'https://github.com/wallee-payment/woocommerce/' ) ) . '" target="_blank" aria-label="' . esc_attr__( 'View Source Code', 'woocommerce-wallee' ) . '">' . esc_html__( 'Source Code', 'woocommerce-wallee' ) . '</a>',
			);
			
			return array_merge( $links, $row_meta );
		}
		
		return (array) $links;
	}
}

WC_Wallee_Admin::instance();
