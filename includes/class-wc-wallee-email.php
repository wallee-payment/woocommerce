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

use Wallee\Sdk\Model\TransactionState;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Wallee_Email.
 *
 * @class WC_Wallee_Email
 */
class WC_Wallee_Email {

	/**
	 * Register email hooks.
	 */
	public static function init() {
		add_action(
			'wallee_transaction_authorized_send_email',
			array(
				__CLASS__,
		  		'send_on_hold_email_when_authorized'
			),
			10,
			1
		);
		add_filter(
			'woocommerce_email_enabled_new_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_cancelled_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_failed_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_customer_on_hold_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_customer_processing_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_customer_completed_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_customer_partially_refunded_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);
		add_filter(
			'woocommerce_email_enabled_customer_refunded_order',
			array(
				__CLASS__,
				'send_email_for_order',
			),
			10,
			2
		);

		add_filter(
			'woocommerce_before_resend_order_emails',
			array(
				__CLASS__,
				'before_resend_email',
			),
			10,
			1
		);
		add_filter(
			'woocommerce_after_resend_order_emails',
			array(
				__CLASS__,
				'after_resend_email',
			),
			10,
			2
		);

		add_filter(
			'woocommerce_germanized_order_email_customer_confirmation_sent',
			array(
				__CLASS__,
				'germanized_send_order_confirmation',
			),
			10,
			2
		);

		add_filter(
			'woocommerce_germanized_order_email_admin_confirmation_sent',
			array(
				__CLASS__,
				'germanized_send_order_confirmation',
			),
			10,
			2
		);

		add_filter( 'woocommerce_email_actions', array( __CLASS__, 'add_email_actions' ), 10, 1 );
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'add_email_classes' ), 100, 1 );
	}

	/**
	 * @param $order_id
	 * @return void
	 */
	public static function send_on_hold_email_when_authorized( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$emails = WC()->mailer()->get_emails();
		if ( isset( $emails['WC_Email_Customer_On_Hold_Order'] ) ) {
			$emails['WC_Email_Customer_On_Hold_Order']->trigger( $order_id );
			update_post_meta( $order_id, '_wallee_on_hold_email_sent', true );
		}

		if ( isset( $emails['WC_Email_New_Order'] ) ) {
			$emails['WC_Email_New_Order']->trigger( $order_id );
		}
	}

	/**
	 * Sends emails.
	 *
	 * @param mixed $enabled enabled.
	 * @param mixed $order order.
	 * @return false|mixed
	 */
	public static function send_email_for_order( $enabled, $order ) {
		if ( ! ( $order instanceof WC_Order ) ) {
			return $enabled;
		}
		if ( isset( $GLOBALS['wallee_resend_email'] ) && $GLOBALS['wallee_resend_email'] ) {
			return $enabled;
		}
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Wallee_Gateway ) {
			$send = get_option( WooCommerce_Wallee::WALLEE_CK_SHOP_EMAIL, 'yes' );
			if ( 'yes' !== $send ) {
				return;
			}

			if ( ! self::is_authorized_on_hold_order( $order ) ) {
				return;
			}
		}
		return $enabled;
	}

	/**
	 * Sets resend email.
	 *
	 * @param mixed $order order.
	 * @return void
	 */
	public static function before_resend_email( $order ) { //phpcs:ignore
		$GLOBALS['wallee_resend_email'] = true;
	}

	/**
	 * After email sent.
	 *
	 * @param mixed $order order.
	 * @param mixed $email email.
	 * @return void
	 */
	public static function after_resend_email( $order, $email ) { //phpcs:ignore
		unset( $GLOBALS['wallee_resend_email'] );
	}

	/**
	 * Add actions to email.
	 *
	 * @param mixed $actions email actions.
	 * @return mixed
	 */
	public static function add_email_actions( $actions ) {

		$to_add = array(
			'woocommerce_order_status_wallee-redirected_to_processing',
			'woocommerce_order_status_wallee-redirected_to_completed',
			'woocommerce_order_status_wallee-redirected_to_on-hold',
			'woocommerce_order_status_wallee-redirected_to_wallee-waiting',
			'woocommerce_order_status_wallee-redirected_to_wallee-manual',
			'woocommerce_order_status_wallee-manual_to_cancelled',
			'woocommerce_order_status_wallee-waiting_to_cancelled',
			'woocommerce_order_status_wallee-manual_to_processing',
			'woocommerce_order_status_wallee-waiting_to_processing',
		);

		if ( class_exists( 'woocommerce_wpml' ) ) {
			global $woocommerce_wpml; //phpcs:ignore
			if ( ! is_null( $woocommerce_wpml ) ) { //phpcs:ignore
				// Add hooks for WPML, for email translations.
				$notifications_all = array(
					'woocommerce_order_status_wallee-redirected_to_processing_notification',
					'woocommerce_order_status_wallee-redirected_to_completed_notification',
					'woocommerce_order_status_wallee-redirected_to_on-hold_notification',
					'woocommerce_order_status_wallee-redirected_to_wallee-waiting_notification',
					'woocommerce_order_status_wallee-redirected_to_wallee-manual_notification',
				);
				$notifications_customer = array(
					'woocommerce_order_status_wallee-manual_to_processing_notification',
					'woocommerce_order_status_wallee-waiting_to_processing_notification',
					'woocommerce_order_status_on-hold_to_processing_notification',
					'woocommerce_order_status_wallee-manual_to_cancelled_notification',
					'woocommerce_order_status_wallee-waiting_to_cancelled_notifcation',
				);

				$wpml_instance = $woocommerce_wpml; //phpcs:ignore
				$email_handler = $wpml_instance->emails;
				foreach ( $notifications_all as $new_action ) {
					add_action(
						$new_action,
						array(
							$email_handler,
							'refresh_email_lang',
						),
						9
					);
					add_action(
						$new_action,
						array(
							$email_handler,
							'new_order_admin_email',
						),
						9
					);
				}
				foreach ( $notifications_customer as $new_action ) {
					add_action(
						$new_action,
						array(
							$email_handler,
							'refresh_email_lang',
						),
						9
					);
				}
			}
		}

		if ( class_exists( 'PLLWC' ) ) {
			add_filter(
				'pllwc_order_email_actions',
				function ( $actions ) {
					$all = array(
						'woocommerce_order_status_postfi-redirected_to_processing',
						'woocommerce_order_status_postfi-redirected_to_completed',
						'woocommerce_order_status_postfi-redirected_to_on-hold',
						'woocommerce_order_status_postfi-redirected_to_postfinancecheckout-waiting',
						'woocommerce_order_status_postfi-redirected_to_postfinancecheckout-manual',
						'woocommerce_order_status_postfi-manual_to_cancelled',
						'woocommerce_order_status_postfi-waiting_to_cancelled',
						'woocommerce_order_status_postfi-manual_to_processing',
						'woocommerce_order_status_postfi-waiting_to_processing',
						'woocommerce_order_status_postfi-redirected_to_processing_notification',
						'woocommerce_order_status_postfi-redirected_to_completed_notification',
						'woocommerce_order_status_postfi-redirected_to_on-hold_notification',
						'woocommerce_order_status_postfi-redirected_to_postfinancecheckout-waiting_notification',
						'woocommerce_order_status_postfi-redirected_to_postfinancecheckout-manual_notification',
					);

					$customers = array(
						'woocommerce_order_status_postfi-manual_to_processing_notification',
						'woocommerce_order_status_postfi-waiting_to_processing_notification',
						'woocommerce_order_status_on-hold_to_processing_notification',
						'woocommerce_order_status_postfi-manual_to_cancelled_notification',
						'woocommerce_order_status_postfi-waiting_to_cancelled_notifcation',
					);

					$actions = array_merge( $actions, $all, $customers );
					return $actions;
				}
			);
		}

		$actions = array_merge( $actions, $to_add );
		return $actions;
	}

	/**
	 * Check Germanized pay email trigger.
	 *
	 * @param mixed $order_id order id.
	 * @param mixed $order order.
	 * @return void
	 */
	public static function check_germanized_pay_email_trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Wallee_Gateway ) {

			$send = get_option( WooCommerce_Wallee::WALLEE_CK_SHOP_EMAIL, 'yes' );
			if ( 'yes' !== $send ) {
				return;
			}

			if ( ! self::is_authorized_on_hold_order( $order ) ) {
				return;
			}

			$mails = WC()->mailer()->get_emails();
			if ( isset( $mails['WC_GZD_Email_Customer_Paid_For_Order'] ) ) {
				$mails['WC_GZD_Email_Customer_Paid_For_Order']->trigger( $order_id );
			}
		}
	}

	/**
	 * Add email classes.
	 *
	 * @param mixed $emails emails.
	 * @return mixed
	 */
	public static function add_email_classes( $emails ) {

		// Germanized has a special email flow.
		if ( isset( $emails['WC_GZD_Email_Customer_Paid_For_Order'] ) ) {
			$email_object = $emails['WC_GZD_Email_Customer_Paid_For_Order'];
			add_action( 'woocommerce_order_status_wallee-redirected_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
			add_action( 'woocommerce_order_status_wallee-manual_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
			add_action( 'woocommerce_order_status_wallee-waiting_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
			add_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( __CLASS__, 'check_germanized_pay_email_trigger' ), 10, 2 );
		}
		if ( function_exists( 'wc_gzd_send_instant_order_confirmation' ) && wc_gzd_send_instant_order_confirmation() ) {
			return $emails;
		}

		foreach ( $emails as $key => $email_object ) {
			switch ( $key ) {
				case 'WC_Email_New_Order':
					add_action( 'woocommerce_order_status_wallee-redirected_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-redirected_to_completed_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-redirected_to_on-hold_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-redirected_to_wallee-waiting_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-redirected_to_wallee-manual_notification', array( $email_object, 'trigger' ), 10, 2 );

					break;

				case 'WC_Email_Cancelled_Order':
					add_action( 'woocommerce_order_status_wallee-manual_to_cancelled_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-waiting_to_cancelled_notification', array( $email_object, 'trigger' ), 10, 2 );
					break;

				case 'WC_Email_Customer_On_Hold_Order':
					add_action( 'woocommerce_order_status_wallee-redirected_to_on-hold_notification', array( $email_object, 'trigger' ), 10, 2 );
					break;

				case 'WC_Email_Customer_Processing_Order':
					add_action( 'woocommerce_order_status_wallee-redirected_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-manual_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
					add_action( 'woocommerce_order_status_wallee-waiting_to_processing_notification', array( $email_object, 'trigger' ), 10, 2 );
					break;

				case 'WC_Email_Customer_Completed_Order':
					// Order complete are always send independent of the source status.
					break;

				case 'WC_Email_Failed_Order':
				case 'WC_Email_Customer_Refunded_Order':
				case 'WC_Email_Customer_Invoice':
					// Do nothing for now.
					break;
			}
		}

		return $emails;
	}

	/**
	 * Germanized send order confirmation.
	 *
	 * @param mixed $email_sent email sent.
	 * @param mixed $order_id order id.
	 * @return bool|mixed
	 */
	public static function germanized_send_order_confirmation( $email_sent, $order_id ) {
		$order = WC_Order_Factory::get_order( $order_id );
		if ( ! ( $order instanceof WC_Order ) ) {
			return $email_sent;
		}
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Wallee_Gateway ) {
			$send = get_option( WooCommerce_Wallee::WALLEE_CK_SHOP_EMAIL, 'yes' );
			if ( 'yes' !== $send ) {
				return true;
			}
		}

		if ( ! self::is_authorized_on_hold_order( $order ) ) {
			return;
		}
		return $email_sent;
	}

	/**
	 * @param WC_Order $order
	 * @return bool
	 */
	private static function is_authorized_on_hold_order( WC_Order $order ) {
		if ( $order->get_status() !== 'on-hold' ) {
			return true;
		}

		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( ! $transaction_info || ( $transaction_info->get_state() !== TransactionState::AUTHORIZED ) ) {
			return false;
		}

		return true;
	}
}

WC_Wallee_Email::init();
