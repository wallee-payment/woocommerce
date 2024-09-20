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
 * Class WC_Wallee_Admin.
 * WC Wallee Admin class
 *
 * @class WC_Wallee_Admin
 */
class WC_Wallee_Admin {

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Wallee_Admin
	 */
	protected static $instance = null;

	/**
	 * Main WooCommerce Wallee Admin Instance.
	 *
	 * Ensures only one instance of WC Wallee Admin is loaded or can be loaded.
	 *
	 * @return WC_Wallee_Admin - Main instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WC Wallee Admin Constructor.
	 */
	protected function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-document.php';
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-transaction.php';
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-notices.php';
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-order-completion.php';
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-order-void.php';
		require_once WC_WALLEE_ABSPATH . 'includes/admin/class-wc-wallee-admin-refund.php';
	}

	/**
	 * Initialise the hooks
	 */
	private function init_hooks() {
		add_action(
			'plugins_loaded',
			array(
				$this,
				'loaded',
			),
			0
		);

		add_filter(
			'woocommerce_get_settings_pages',
			array(
				$this,
				'add_settings',
			)
		);

		add_filter(
			'plugin_action_links_' . WC_WALLEE_PLUGIN_BASENAME,
			array(
				$this,
				'plugin_action_links',
			)
		);

		add_filter(
			'woocommerce_hidden_order_itemmeta',
			array(
				$this,
				'hide_wallee_order_item_meta',
			),
			10,
			1
		);

		add_action(
			'woocommerce_order_item_add_action_buttons',
			array(
				$this,
				'render_authorized_action_buttons',
			),
			1
		);

		add_action(
			'wp_ajax_woocommerce_wallee_update_order',
			array(
				$this,
				'update_order',
			)
		);
		add_action(
			'admin_init',
			array(
				$this,
				'handle_woocommerce_active',
			)
		);

		add_action(
			'woocommerce_admin_order_actions',
			array(
				$this,
				'remove_not_wanted_order_actions',
			),
			10,
			2
		);

		add_action(
			'woocommerce_after_edit_attribute_fields',
			array(
				$this,
				'display_attribute_options_edit',
			),
			10,
			0
		);

		add_action(
			'woocommerce_after_add_attribute_fields',
			array(
				$this,
				'display_attribute_options_add',
			),
			10,
			0
		);
	}

	/**
	 * Handle plugin deactivation
	 */
	public function handle_woocommerce_active() {
		// WooCommerce plugin not activated.
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			// Deactivate myself.
			add_action(
				'admin_notices',
				array(
					'WC_Wallee_Admin_Notices',
					'plugin_deactivated',
				)
			);
		}
	}

	/**
	 * Render authorized aciton buttons
	 *
	 * @param WC_Order $order order.
	 */
	public function render_authorized_action_buttons( WC_Order $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Wallee_Gateway ) {
			$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
			if ( $transaction_info->get_state() === \Wallee\Sdk\Model\TransactionState::AUTHORIZED ) {
				if ( WC_Wallee_Entity_Completion_Job::count_running_completion_for_transaction(
					$transaction_info->get_space_id(),
					$transaction_info->get_transaction_id()
				) > 0 || WC_Wallee_Entity_Void_Job::count_running_void_for_transaction(
					$transaction_info->get_space_id(),
					$transaction_info->get_transaction_id()
				) > 0 ) {
					echo '<span class="wallee-action-in-progress">' . esc_html__( 'There is a completion/void in progress.', 'woo-wallee' ) . '</span>';
					echo '<button type="button" class="button wallee-update-order">' . esc_html__( 'Update', 'woo-wallee' ) . '</button>';
				} else {
					echo '<button type="button" class="button wallee-void-show">' . esc_html__( 'Void', 'woo-wallee' ) . '</button>';
					echo '<button type="button" class="button button-primary wallee-completion-show">' . esc_html__( 'Completion', 'woo-wallee' ) . '</button>';
				}
			}
		}
	}

	/**
	 * Remove unwanted order actions
	 *
	 * @param array $actions actions.
	 * @param WC_Order $order order.
	 * @return array
	 */
	public function remove_not_wanted_order_actions( array $actions, WC_Order $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Wallee_Gateway ) {
			if ( $order->has_status( 'on-hold' ) ) {
				unset( $actions['processing'] );
				unset( $actions['complete'] );
			}
		}
		return $actions;
	}

	/**
	 * Init WooCommerce Wallee when plugins are loaded.
	 */
	public function loaded() {
		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'enque_script_and_css',
			)
		);
	}

	/**
	 * Enqueue the script and css files
	 */
	public function enque_script_and_css() {
		$screen = get_current_screen();
		$post_type = $screen ? $screen->post_type : '';
		if ( 'shop_order' == $post_type ) {
			wp_enqueue_style(
				'woo-wallee-admin-styles',
				WooCommerce_Wallee::instance()->plugin_url() . '/assets/css/admin.css',
				array(),
				true
			);
			wp_enqueue_script(
				'wallee-admin-js',
				WooCommerce_Wallee::instance()->plugin_url() . '/assets/js/admin/management.js',
				array(
					'jquery',
					'wc-admin-meta-boxes',
				),
				true,
				true
			);

			$localize = array(
				'i18n_do_void'  => esc_html__( 'Are you sure you wish to process this void? This action cannot be undone.', 'woo-wallee' ),
				'i18n_do_completion' => esc_html__( 'Are you sure you wish to process this completion? This action cannot be undone.', 'woo-wallee' ),
			);
			wp_localize_script( 'wallee-admin-js', 'wallee_admin_js_params', $localize );
		}
	}

	/**
	 * Hide wallee order item meta
	 *
	 * @param array $arr array.
	 * @return array
	 */
	public function hide_wallee_order_item_meta( $arr ) {
		$arr[] = '_wallee_unique_line_item_id';
		$arr[] = '_wallee_coupon_discount_line_item_id';
		$arr[] = '_wallee_coupon_discount_line_item_key';
		$arr[] = '_wallee_coupon_discount_line_item_discounts';
		return $arr;
	}

	/**
	 * Update the order
	 */
	public function update_order() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {// phpcs:ignore
			wp_die( -1 );
		}

		if ( ! isset( $_POST['order_id'] ) ) {
			return;
		}
		$order_id = absint( sanitize_key( wp_unslash( $_POST['order_id'] ) ) );
		$order = WC_Order_Factory::get_order( $order_id );
		try {
			do_action( 'wallee_update_running_jobs', $order );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
		wp_send_json_success();
	}

	/**
	 * Add WooCommerce Wallee Settings Tab
	 *
	 * @param array $integrations integrations.
	 * @return array
	 */
	public function add_settings( $integrations ) {
		$integrations[] = new WC_Wallee_Admin_Settings_Page();
		return $integrations;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wallee' ) . '" aria-label="' .
					esc_html__( 'View Settings', 'woo-wallee' ) . '">' . esc_html__( 'Settings', 'woo-wallee' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Store attribute options
	 *
	 * @param mixed $product product.
	 * @param mixed $data_storage data storage.
	 */
	public function store_attribute_options( $product, $data_storage ) { //phpcs:ignore
		global $wallee_attributes_options;
		if ( ! empty( $wallee_attributes_options ) ) {
			$product->add_meta_data( '_wallee_attribute_options', $wallee_attributes_options, true );
		}
	}

	/**
	 * Display attribute options edit screen
	 */
	public function display_attribute_options_edit() {
		if ( ! isset( $_GET['edit'] ) ) {// phpcs:ignore
			return;
		} else {
			$edit = esc_url_raw( wp_unslash( $_GET['edit'] ) );// phpcs:ignore
		}
		$edit = absint( $edit );
		$checked = false;
		$attribute_options = WC_Wallee_Entity_Attribute_Options::load_by_attribute_id( $edit );
		if ( $attribute_options->get_id() > 0 && $attribute_options->get_send() ) {
			$checked = true;
		}
		echo esc_html(
			'<tr class="form-field form-required">
					<th scope="row" valign="top">
							<label for="wallee_attribute_option_send">'
			) . esc_html__( 'Send attribute to wallee.', 'woo-wallee' ) . esc_html(
						'</label>
					</th>
						<td>
								<input name="wallee_attribute_option_send" id="wallee_attribute_option_send" type="checkbox" value="1" '
			) . esc_attr( checked( $checked, true, false ) ) . esc_html(
							'/>
							<p class="description">'
			) . esc_html__( 'Should this product attribute be sent to wallee as line item attribute?', 'woo-wallee' ) . esc_html(
							'</p>
						</td>
				</tr>'
			);
	}

	/**
	 * Display attribute options add screen
	 */
	public function display_attribute_options_add() {
		echo esc_html(
			'<div class="form-field">
				<label for="wallee_attribute_option_send"><input name="wallee_attribute_option_send" id="wallee_attribute_option_send" type="checkbox" value="1">'
		) . esc_html__( 'Send attribute to wallee.', 'woo-wallee' ) . esc_html(
				'</label>
				<p class="description">'
		) . esc_html__( 'Should this product attribute be sent to wallee as line item attribute?', 'woo-wallee' ) .
		esc_html(
				'</p>
			</div>'
		);
	}
}

WC_Wallee_Admin::instance();
