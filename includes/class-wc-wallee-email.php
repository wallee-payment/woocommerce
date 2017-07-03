<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This class handles the email settings fo wallee.
 */
class WC_Wallee_Email {

	/**
	 * Register email hooks
	 */
	public static function init(){
		add_filter('woocommerce_email_enabled_new_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_cancelled_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_failed_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_customer_on_hold_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_customer_processing_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_customer_completed_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_customer_partially_refunded_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		add_filter('woocommerce_email_enabled_customer_refunded_order', array(
			__CLASS__,
			'send_email_for_order' 
		), 10, 2);
		
		add_filter('woocommerce_before_resend_order_emails', array(
			__CLASS__,
			'before_resend_email' 
		), 10, 1);
		add_filter('woocommerce_after_resend_order_emails', array(
			__CLASS__,
			'after_resend_email' 
		), 10, 2);
	}

	public static function send_email_for_order($enabled, $order){
		if (!($order instanceof WC_Order)) {
			return $enabled;
		}
		if (isset($GLOBALS['_wallee_resend_email']) && $GLOBALS['_wallee_resend_email']) {
			return $enabled;
		}
		$send = get_option("wc_wallee_shop_email", "yes");
		if ($send != "yes") {
			return false;
		}
		return $enabled;
	}

	public static function before_resend_email($order){
		$GLOBALS['_wallee_resend_email'] = true;
	}

	public static function after_resend_email($order, $email){
		unset($GLOBALS['_wallee_resend_email']);
	}
}

WC_Wallee_Email::init();