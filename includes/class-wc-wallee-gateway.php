<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This class implements the wallee gateways
 */
class WC_Wallee_Gateway extends WC_Payment_Gateway {
	private $payment_method_configuration_id;
	
	//Contains a users saved tokens for this gateway.
	protected $tokens = array();
	private $wallee_payment_method_configuration_id;
	private $wallee_payment_method_configuration = null;
	private $translated_title = null;
	private $translated_description = null;
	private $show_description = true;
	private $show_icon = true;

	public function __construct(WC_Wallee_Entity_Method_Configuration $method){
		$this->wallee_payment_method_configuration_id = $method->get_id();
		$this->id = 'wallee_' . $method->get_id();
		$this->has_fields = false;
		$this->method_title = $method->get_configuration_name();
		$this->method_description = sprintf(__('The general settings for Wallee can be found <a href="%s" >here</a>', 'woocommerce-wallee'), 
				admin_url('admin.php?page=wc-settings&tab=wallee'));
		$this->icon = $method->get_image();
		
		//We set the title and description here, as some plugin access the variables directly.
		$this->title = $method->get_configuration_name();
		$this->description = "";
		
		$this->translated_title = $method->get_title();
		$this->translated_description = $method->get_description();
		$this->supports = array(
			'products',
			'refunds' 
		);
		
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Define user set variables.
		$this->enabled = $this->get_option('enabled');
		$this->show_description = $this->get_option('show_description');
		$this->show_icon = $this->get_option('show_icon');
		
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options' 
		));
	}

	/**
	 * Returns the payment method configuration.
	 *
	 * @return WC_Wallee_Entity_Method_Configuration
	 */
	public function get_payment_method_configuration(){
		if ($this->wallee_payment_method_configuration === null) {
			$this->wallee_payment_method_configuration = WC_Wallee_Entity_Method_Configuration::load_by_id(
					$this->wallee_payment_method_configuration_id);
		}
		return $this->wallee_payment_method_configuration;
	}

	/**
	 * Return the gateway's title fontend.
	 *
	 * @return string
	 */
	public function get_title(){
		$title = $this->title;
		$translated = WC_Wallee_Helper::instance()->translate($this->translated_title);
		if ($translated !== null) {
			$title = $translated;
		}
		return apply_filters('woocommerce_gateway_title', $title, $this->id);
	}

	/**
	 * Return the gateway's description frontend.
	 *
	 * @return string
	 */
	public function get_description(){
		$description = "";
		if ($this->show_description == 'yes') {
			$translated = WC_Wallee_Helper::instance()->translate($this->translated_description);
			if ($translated !== null) {
				$description = $translated;
			}
		}
		return apply_filters('woocommerce_gateway_description', $description, $this->id);
	}

	/**
	 * Return the gateway's icon.
	 * @return string
	 */
	public function get_icon(){
		$icon = "";
		if ($this->show_icon == 'yes') {
			$space_id = $this->get_payment_method_configuration()->get_space_id();
			$space_view_id = get_option('wc_wallee_space_view_id');
			$language = get_locale();
			
			$url = WC_Wallee_Helper::instance()->get_resource_url($this->icon, $language, $space_id, $space_view_id);
			$icon = '<img src="' . WC_HTTPS::force_https_url($url) . '" alt="' . esc_attr($this->get_title()) . '" width="35px" />';
		}
		
		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => sprintf(__('Enable %s', 'woocommerce-wallee'), $this->method_title),
				'default' => 'yes' 
			),
			'title_value' => array(
				'title' => __('Title', 'woocommerce'),
				'type' => 'info',
				'value' => $this->get_title(),
				'description' => __('This controls the title which the user sees during checkout.', 'woocommerce') 
			),
			'description_value' => array(
				'title' => __('Description', 'woocommerce'),
				'type' => 'info',
				'value' => !empty($this->get_description()) ? $this->get_description() : __('[not set]', 'woocommerce-wallee'),
				'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce') 
			),
			'show_description' => array(
				'title' => __('Show description', 'woocommerce-wallee'),
				'type' => 'checkbox',
				'label' => __('Yes', 'woocommerce-wallee'),
				'default' => 'yes',
				'description' => __("Show the payment method's description on the checkout page.", 'woocommerce'),
				'desc_tip' => true 
			),
			'show_icon' => array(
				'title' => __('Show method image', 'woocommerce-wallee'),
				'type' => 'checkbox',
				'label' => __('Yes', 'woocommerce-wallee'),
				'default' => 'yes',
				'description' => __("Show the payment method's image on the checkout page.", 'woocommerce'),
				'desc_tip' => true 
			) 
		);
	}

	/**
	 * Generate info HTML.
	 *
	 * @param  mixed $key
	 * @param  mixed $data
	 * @return string
	 */
	public function generate_info_html($key, $data){
		$field_key = $this->get_field_key($key);
		$defaults = array(
			'title' => '',
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'desc_tip' => true,
			'description' => '',
			'custom_attributes' => array() 
		);
		
		$data = wp_parse_args($data, $defaults);
		
		ob_start();
		?>
<tr valign="top">
	<th scope="row" class="titledesc">
							<?php echo $this->get_tooltip_html( $data ); ?>
							<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<div class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?> >
								<?php echo esc_attr($data['value']); ?>
						</div>
		</fieldset>
	</td>
</tr>
<?php
		
		return ob_get_clean();
	}

	/**
	 * Validate Info Field.
	 *
	 * @param  string $key Field key
	 * @param  string|null $value Posted Value
	 * @return void
	 */
	public function validate_info_field($key, $value){
		return;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available(){
		$is_available = parent::is_available();
		
		if (!$is_available) {
			return false;
		}
		//The gateways are always available during order total caluclation, as other plugins could need them.
		if (isset($GLOBALS['_wc_wallee_calculating']) && $GLOBALS['_wc_wallee_calculating']) {
			return true;
		}
		
		try {
			$possible_methods = WC_Wallee_Service_Transaction::instance()->get_possible_payment_methods();
			$possible = false;
			foreach ($possible_methods as $possible_method) {
				if ($possible_method->getId() == $this->get_payment_method_configuration()->get_configuration_id()) {
					$possible = true;
					break;
				}
			}
			if (!$possible) {
				return false;
			}
		}
		catch (\Wallee\Sdk\Http\ConnectionException $e) {
			return false;
		}
		
		return true;
	}

	/**
	 * Check if the gateway has fields on the checkout.
	 *
	 * @return bool
	 */
	public function has_fields(){
		return true;
	}

	public function payment_fields(){
		wp_enqueue_script('wallee-remote-checkout-js', WC_Wallee_Service_Transaction::instance()->get_javascript_url(), array(
			'jquery' 
		), null, true);
		
		wp_enqueue_script('wallee-checkout-js', WooCommerce_Wallee::instance()->plugin_url() . '/assets/js/frontend/checkout.js', 
				array(
					'jquery',
					'wallee-remote-checkout-js' 
				), null, true);
		
		parent::payment_fields();
		?>
		
<div id="payment-form-<?php echo $this->id?>"></div>
<div id="wallee-method-configuration-<?php echo $this->id?>"
	class="wallee-method-configuration" style="display: none;"
	data-method-id="<?php echo $this->id; ?>"
	data-configuration-id="<?php echo $this->get_payment_method_configuration()->get_configuration_id(); ?>"
	data-container-id="payment-form-<?php echo $this->id?>" data-description-available="<?php var_export(!empty($this->get_description()));?>"></div>
<?php
	}

	/**
	 * Validate frontend fields.
	 * @return bool
	 */
	public function validate_fields(){
		return true;
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment($order_id){
		try {
			$order = wc_get_order($order_id);
			$session_handler = WC()->session;
			$wallee_space_id = $session_handler->get('wallee_space_id');
			$wallee_transaction_id = $session_handler->get('wallee_transaction_id');
			
			$transaction_service = WC_Wallee_Service_Transaction::instance();
			
			$transaction = $transaction_service->update_transaction($wallee_transaction_id, $wallee_space_id, $order, true);
			$transaction_service->update_transaction_info($transaction, $order);
			
			$order->add_meta_data('_wallee_linked_space_id', $transaction->getLinkedSpaceId(), true);
			$order->add_meta_data('_wallee_transaction_id', $transaction->getId(), true);
			
			$session_handler->set('order_awaiting_payment', false);
			wc_maybe_reduce_stock_levels($order->get_id());
			
			$order->save();
			
			WC_Wallee_Helper::instance()->destroy_current_cart_id();
			
			return array(
				'result' => 'success',
				'wallee' => 'true' 
			);
		}
		catch (Exception $e) {
			WooCommerce_Wallee::instance()->add_notice($e->getMessage(), 'error');
			return array(
				'result' => 'failure',
				'reload' => 'true' 
			);
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund($order_id, $amount = null, $reason = ''){
		global $wpdb;
		
		if (!isset($GLOBALS['wallee_refund_id'])) {
			return new WP_Error('wallee_error', __('There was a problem creating the refund.', 'woocommerce-wallee'));
		}
		/**
		 * @var WC_Order_Refund $refund
		 */
		$refund = WC_Order_Factory::get_order($GLOBALS['wallee_refund_id']);
		$order = WC_Order_Factory::get_order($order_id);
		
		try {
			WC_Wallee_Admin_Refund::execute_refund($order, $refund);
		}
		catch (Exception $e) {
			return new WP_Error('wallee_error', $e->getMessage());
		}
		return true;
	}
}