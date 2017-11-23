<?php
/**
 * Plugin Name: WooCommerce Wallee
 * Plugin URI: https://wordpress.org/plugins/woo-wallee
 * Description: Process WooCommerce payments with Wallee
 * Version: 1.0.6
 * License: Apache2
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Author: customweb GmbH
 * Author URI: https://www.customweb.com
 * Requires at least: 4.4
 * Tested up to: 4.9
 *
 * Text Domain: woocommerce-wallee
 * Domain Path: /languages/
 *
 */
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}

if (!class_exists('WooCommerce_Wallee')) {

	/**
	 * Main WooCommerce Wallee Class
	 *
	 * @class WooCommerce_Wallee
	 */
	final class WooCommerce_Wallee {
		
		/**
		 * WooCommerce Wallee version.
		 *
		 * @var string
		 */
		private $version = '1.0.6';
		
		/**
		 * The single instance of the class.
		 *
		 * @var WooCommerce_Wallee
		 */
		protected static $_instance = null;
		private $logger = null;

		/**
		 * Main WooCommerce Wallee Instance.
		 *
		 * Ensures only one instance of WooCommerce Wallee is loaded or can be loaded.
		 *
		 * @return WooCommerce_Wallee - Main instance.
		 */
		public static function instance(){
			if (self::$_instance === null) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * WooCommerce Wallee Constructor.
		 */
		protected function __construct(){
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		public function get_version(){
			return $this->version;
		}

		/**
		 * Define WC Wallee Constants.
		 */
		private function define_constants(){
			$this->define('WC_WALLEE_PLUGIN_FILE', __FILE__);
			$this->define('WC_WALLEE_ABSPATH', dirname(__FILE__) . '/');
			$this->define('WC_WALLEE_PLUGIN_BASENAME', plugin_basename(__FILE__));
			$this->define('WC_WALLEE_VERSION', $this->version);
			$this->define('WC_WALLEE_REQUIRED_PHP_VERSION', '5.6');
			$this->define('WC_WALLEE_REQUIRED_WP_VERSION', '4.4');
			$this->define('WC_WALLEE_REQUIRED_WC_VERSION', '3.0');
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes(){
			/**
			 * Class autoloader.
			 */
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-autoloader.php');
			require_once (WC_WALLEE_ABSPATH . 'wallee-sdk/autoload.php');
			
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-migration.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-email.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-return-handler.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-webhook-handler.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-unique-id.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-customer-document.php');
			require_once (WC_WALLEE_ABSPATH . 'includes/class-wc-wallee-cron.php');
			
			if (is_admin()) {
				require_once (WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin.php');
			}
		}

		private function init_hooks(){
			register_activation_hook(__FILE__, array(
				'WC_Wallee_Migration',
				'install_wallee_db' 
			));
			register_activation_hook(__FILE__, array(
				'WC_Wallee_Cron',
				'activate' 
			));
			register_deactivation_hook(__FILE__, array(
				'WC_Wallee_Cron',
				'deactivate' 
			));
			
			add_action('plugins_loaded', array(
				$this,
				'loaded' 
			), 0);
			add_action('init', array(
				$this,
				'register_order_statuses' 
			));
			add_filter('wc_order_is_editable', array(
				$this,
				'order_editable_check' 
			), 10, 2);
			add_filter('woocommerce_before_calculate_totals', array(
				$this,
				'before_calculate_totals' 
			), 10);
			add_filter('woocommerce_after_calculate_totals', array(
				$this,
				'after_calculate_totals' 
			), 10);
		}

		/**
		 * Load Localization files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/woocommerce-wallee/woocommerce-wallee-LOCALE.mo
		 */
		public function load_plugin_textdomain(){
			$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-wallee');
			
			load_textdomain('woocommerce-wallee', WP_LANG_DIR . '/woocommerce-wallee/woocommerce-wallee' . $locale . '.mo');
			load_plugin_textdomain('woocommerce-wallee', false, plugin_basename(dirname(__FILE__)) . '/languages');
		}

		/**
		 * Init WooCommerce Wallee when plugins are loaded. 
		 */
		public function loaded(){
			
			// Set up localisation.
			$this->load_plugin_textdomain();
			
			add_filter('woocommerce_payment_gateways', array(
				$this,
				'add_gateways' 
			));
			add_filter('wc_order_statuses', array(
				$this,
				'add_order_statuses' 
			));
		}

		public function register_order_statuses(){
			register_post_status('wc-wallee-redirected',
					array(
						'label' => 'Redirected',
						'public' => true,
						'exclude_from_search' => false,
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true,
						'label_count' => _n_noop('Redirected <span class="count">(%s)</span>', 'Redirected <span class="count">(%s)</span>') 
					));
			register_post_status('wc-wallee-waiting',
					array(
						'label' => 'Waiting',
						'public' => true,
						'exclude_from_search' => false,
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true,
						'label_count' => _n_noop('Waiting <span class="count">(%s)</span>', 'Waiting <span class="count">(%s)</span>') 
					));
			register_post_status('wc-wallee-manual',
					array(
						'label' => 'Manual Decision',
						'public' => true,
						'exclude_from_search' => false,
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true,
						'label_count' => _n_noop('Manual Decision <span class="count">(%s)</span>', 'Manual Decision <span class="count">(%s)</span>') 
					));
		}

		public function add_order_statuses($order_statuses){
			$order_statuses['wc-wallee-redirected'] = _x('Redirected', 'Order status', 'woocommerce');
			$order_statuses['wc-wallee-waiting'] = _x('Waiting', 'Order status', 'woocommerce');
			$order_statuses['wc-wallee-manual'] = _x('Manual Decision', 'Order status', 'woocommerce');
			
			return $order_statuses;
		}

		public function order_editable_check($allowed, WC_Order $order = null){
			if ($order == null) {
				return $allowed;
			}
			if ($order->get_meta('_wallee_authorized', true)) {
				return false;
			}
			return $allowed;
		}

		public function before_calculate_totals(WC_Cart $cart){
			$GLOBALS['_wc_wallee_calculating'] = true;
			;
		}

		public function after_calculate_totals(WC_Cart $cart){
			unset($GLOBALS['_wc_wallee_calculating']);
		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 */
		public function add_gateways($methods){
			$space_id = get_option('wc_wallee_space_id');
			$wallee_method_configurations = WC_Wallee_Entity_Method_Configuration::load_by_states_and_space_id($space_id,
					array(
						WC_Wallee_Entity_Method_Configuration::STATE_ACTIVE,
						WC_Wallee_Entity_Method_Configuration::STATE_INACTIVE 
					));
			try {
				foreach ($wallee_method_configurations as $configuration) {
					$methods[] = new WC_Wallee_Gateway($configuration);
				}
			}			
			catch (\Wallee\Sdk\ApiException $e) {
				if ($e->getCode() === 401) {
					// Ignore it because we simply are not allowed to access the API
				}
				else {
					throw $e;
				}
			}
			
			return $methods;
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define($name, $value){
			if (!defined($name)) {
				define($name, $value);
			}
		}

		public function log($message, $level = WC_Log_Levels::WARNING){
			if ($this->logger == null) {
				$this->logger = new WC_Logger();
			}
			
			$this->logger->log($level, $message, array(
				'source' => 'woocommerce-wallee' 
			));
			
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log("Woocommerce Wallee: " . $message);
			}
		}

		/**
		 * Add a WooCommerce notification message
		 *
		 * @param string $message Notification message
		 * @param string $type One of notice, error or success (default notice)
		 * @return $this
		 */
		public function add_notice($message, $type = 'notice'){
			$type = in_array($type, array(
				'notice',
				'error',
				'success' 
			)) ? $type : 'notice';
			wc_add_notice($message, $type);
		}

		/**
		 * Get the plugin url.
		 * @return string
		 */
		public function plugin_url(){
			return untrailingslashit(plugins_url('/', __FILE__));
		}

		/**
		 * Get the plugin path.
		 * @return string
		 */
		public function plugin_path(){
			return untrailingslashit(plugin_dir_path(__FILE__));
		}
	}
}

WooCommerce_Wallee::instance();