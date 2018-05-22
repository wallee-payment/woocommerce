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
 * Adds Wallee settings to WooCommerce Settings Tabs
 */
class WC_Wallee_Admin_Settings_Page extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct(){
		$this->id = 'wallee';
		$this->label = 'wallee';
		
		add_filter('woocommerce_settings_tabs_array', array(
			$this,
			'add_settings_page' 
		), 20);
		add_action('woocommerce_settings_' . $this->id, array(
			$this,
			'settings_tab' 
		));
		add_action('woocommerce_settings_save_' . $this->id, array(
			$this,
			'save' 
		));
		
		add_action('woocommerce_update_options_' . $this->id, array(
			$this,
			'update_settings' 
		));
	}

	public function add_settings_tab($settings_tabs){
		$settings_tabs[$this->id] = 'wallee';
		return $settings_tabs;
	}

	public function settings_tab(){
		woocommerce_admin_fields($this->get_settings());
	}
	
	public function save(){
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
		
	}

	public function update_settings(){
	    WC_Wallee_Helper::instance()->reset_api_client();
	    $user_id = get_option(WooCommerce_Wallee::CK_APP_USER_ID);
	    $user_key = get_option(WooCommerce_Wallee::CK_APP_USER_KEY);
		if (!empty($user_id) && !empty($user_key)) {
		    $error = '';
		    try{
		        WC_Wallee_Service_Method_Configuration::instance()->synchronize();
		    }
		    catch (Exception $e) {
		        WooCommerce_Wallee::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_Wallee::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
		        $error .= ' '. 
		            __('Could not update payment method configuration.', 'woocommerce-wallee');
		    }
		    try{
		        WC_Wallee_Service_Webhook::instance()->install();
		    }
		    catch (Exception $e) {
		        WooCommerce_Wallee::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_Wallee::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
		        $error .= ' '.
		            __('Could not install webhooks, please check the feature is active for your space.', 'woocommerce-wallee');
		    }
		    try{
		        WC_Wallee_Service_Manual_Task::instance()->update();
		    }
		    catch (Exception $e) {
		        WooCommerce_Wallee::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_Wallee::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
		        $error .= ' '.
		            __('Could not update the manual task list.', 'woocommerce-wallee');
		    }
		    try {
		        do_action('wc_wallee_settings_changed');
		    }
		    catch (Exception $e) {
		        WooCommerce_Wallee::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_Wallee::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
		        $error .= ' '. $e->getMessage();
		    }
		    if(!empty($error)){
		        $error .=
		            __('Please check your credentials.', 'woocommerce-wallee') .' '.$error;
		        WC_Admin_Settings::add_error($error);
		        
		    }			
			$this->delete_provider_transients();
		}
		
	}

	private function delete_provider_transients(){
		$transients = array(
			'wc_wallee_currencies',
			'wc_wallee_label_description_groups',
			'wc_wallee_label_descriptions',
			'wc_wallee_languages',
			'wc_wallee_payment_connectors',
			'wc_wallee_payment_methods' 
		);
		foreach ($transients as $transient) {
			delete_transient($transient);
		}
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings(){
		$settings = array(
			
			array(
				'title' => __('General', 'woocommerce-wallee'),
				'desc' => sprintf(
						__('To use this extension, a wallee account is required. Sign up <a href="%s" target="_blank">here</a>.',
								'woocommerce-wallee'), 'https://app-wallee.com/user/signup'),
				'type' => 'title',
				'id' => 'general_options' 
			),
			
			array(
				'title' => __('User Id', 'woocommerce-wallee'),
				'desc_tip' => __('The Application User needs to have full permissions in the space this shop is linked to.', 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_APP_USER_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => __('(required)', 'woocommerce-wallee') 
			),
			
			array(
				'title' => __('Application Key', 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_APP_USER_KEY,
				'type' => 'password',
				'css' => 'min-width:300px;',
				'desc' => __('(required)', 'woocommerce-wallee') 
			),
			
			array(
				'title' => __('Space Id', 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_SPACE_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => __('(required)', 'woocommerce-wallee') 
			),
			
			array(
				'title' => __('Space View Id', 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_SPACE_VIEW_ID,
				'type' => 'text',
				'css' => 'min-width:300px;' 
			),
			
			array(
				'type' => 'sectionend',
				'id' => 'general_options' 
			),
			
			array(
				'title' => __('Email Options', 'woocommerce-wallee'),
				'type' => 'title',
				'id' => 'email_options' 
			),
			
			array(
				'title' => __('Send Order Email', 'woocommerce-wallee'),
				'desc' => __("Send the Woocommerce's order email.", 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_SHOP_EMAIL,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			
			array(
				'type' => 'sectionend',
				'id' => 'email_options' 
			),
			
			array(
				'title' => __('Document Options', 'woocommerce-wallee'),
				'type' => 'title',
				'id' => 'document_options' 
			),
			
			array(
				'title' => __('Invoice Download', 'woocommerce-wallee'),
				'desc' => __("Allow customer's to download the invoice.", 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_CUSTOMER_INVOICE,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			array(
				'title' => __('Packing Slip Download', 'woocommerce-wallee'),
				'desc' => __("Allow customer's to download the packing slip.", 'woocommerce-wallee'),
			    'id' => WooCommerce_Wallee::CK_CUSTOMER_PACKING,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			
			array(
				'type' => 'sectionend',
				'id' => 'document_options' 
			) 
		
		);
		
		return apply_filters('wc_wallee_settings', $settings);
	}
}
