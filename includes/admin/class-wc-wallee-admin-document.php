<?php
/**
 *
 * WC_Wallee_Admin_Document Class
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (https://www.wallee.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Shows the document downloads buttons and handles the downloads in the order overview.
 */
class WC_Wallee_Admin_Document {

	/**
	 * Init WC_Wallee_Admin_Document.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'add_meta_boxes',
			array(
				__CLASS__,
				'add_meta_box',
			),
			40
		);
		add_action(
			'woocommerce_admin_order_actions_end',
			array(
				__CLASS__,
				'add_buttons_to_overview',
			),
			12,
			1
		);
		add_action(
			'admin_init',
			array(
				__CLASS__,
				'download_document',
			)
		);
	}

	/**
	 * Add buttons to overview.
	 *
	 * @param WC_Order $order Wc Order.
	 * @return void
	 */
	public static function add_buttons_to_overview( WC_Order $order ) {
		$method = wc_get_payment_gateway_by_order( $order );
		if ( ! ( $method instanceof WC_Wallee_Gateway ) ) {
			return;
		}
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );

		if ( $transaction_info->get_id() === null ) {
			return;
		}
		if ( in_array(
			$transaction_info->get_state(),
			array(
				\Wallee\Sdk\Model\TransactionState::COMPLETED,
				\Wallee\Sdk\Model\TransactionState::FULFILL,
				\Wallee\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {

			$url   = wp_nonce_url(
				add_query_arg(
					array(
						'post'                        => $order->get_id(),
						'refer'                       => 'overview',
						'wallee_admin' => 'download_invoice',
					),
					admin_url( 'post.php' )
				),
				'download_invoice',
				'nonce'
			);
			$title = esc_attr( __( 'Invoice', 'woo-wallee' ) );
			printf( '<a class="button tips wallee-action-button  wallee-button-download-invoice" href="%1s" data-tip="%2s">%2s</a>', esc_url( $url ), esc_textarea( $title ), esc_textarea( $title ) );
		}
		if ( $transaction_info->get_state() === \Wallee\Sdk\Model\TransactionState::FULFILL ) {
			$url   = wp_nonce_url(
				add_query_arg(
					array(
						'post'                        => $order->get_id(),
						'refer'                       => 'overview',
						'wallee_admin' => 'download_packing',
					),
					admin_url( 'post.php' )
				),
				'download_packing',
				'nonce'
			);
			$title = esc_attr( __( 'Packing Slip', 'woo-wallee' ) );
			printf( '<a class="button tips wallee-action-button wallee-button-download-packingslip" href="%1s" data-tip="%2s">%2s</a>', esc_url( $url ), esc_textarea( $title ), esc_textarea( $title ) );
		}
	}

	/**
	 * Add WC Meta boxes.
	 */
	public static function add_meta_box() {
		global $post;
		if ( 'shop_order' !== $post->post_type ) {
			return;
		}
		$order  = WC_Order_Factory::get_order( $post->ID );
		$method = wc_get_payment_gateway_by_order( $order );
		if ( ! ( $method instanceof WC_Wallee_Gateway ) ) {
			return;
		}
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $transaction_info->get_id() !== null && in_array(
			$transaction_info->get_state(),
			array(
				\Wallee\Sdk\Model\TransactionState::COMPLETED,
				\Wallee\Sdk\Model\TransactionState::FULFILL,
				\Wallee\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {
			add_meta_box(
				'woocommerce-order-wallee-documents',
				__( 'wallee Documents', 'woo-wallee' ),
				array(
					__CLASS__,
					'output',
				),
				'shop_order',
				'side',
				'default'
			);
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function output( $post ) {
		global $post;

		$order  = WC_Order_Factory::get_order( $post->ID );
		$method = wc_get_payment_gateway_by_order( $order );
		if ( ! ( $method instanceof WC_Wallee_Gateway ) ) {
			return;
		}
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $transaction_info->get_id() === null ) {
			return;
		}
		if ( in_array(
			$transaction_info->get_state(),
			array(
				\Wallee\Sdk\Model\TransactionState::COMPLETED,
				\Wallee\Sdk\Model\TransactionState::FULFILL,
				\Wallee\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {

			?>
<ul class="woocommerce-order-admin-wallee-downloads">
	<li><a
		href="
			<?php

			echo esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'post'                        => $order->get_id(),
							'refer'                       => 'edit',
							'wallee_admin' => 'download_invoice',
						),
						admin_url( 'post.php' )
					),
					'download_invoice',
					'nonce'
				)
			);
			?>
			"
		class="wallee-admin-download wallee-admin-download-invoice button"><?php esc_attr_e( 'Invoice', 'woo-wallee' ); ?></a></li>

					<?php if ( $transaction_info->get_state() === \Wallee\Sdk\Model\TransactionState::FULFILL ) : ?>
						<li><a
		href="
						<?php

						echo esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'post'  => $order->get_id(),
										'refer' => 'edit',
										'wallee_admin' => 'download_packing',
									),
									admin_url( 'post.php' ),
									true
								),
								'download_packing',
								'nonce'
							)
						);
						?>
				"
		class="wallee-admin-download wallee-admin-download-packingslip button"><?php esc_attr_e( 'Packing Slip', 'woo-wallee' ); ?></a></li>
					<?php endif; ?>
					</ul>
			<?php
		}
	}

	/**
	 * Admin pdf actions callback.
	 * Within admin by default only administrator and shop managers have permission to view, create, cancel invoice.
	 */
	public static function download_document() {
		if ( ! self::is_download_request() ) {
			return;
		}

		// sanitize data and verify nonce.
		$action = isset( $_GET['wallee_admin'] ) ? sanitize_key( $_GET['wallee_admin'] ) : null;
		$nonce  = isset( $_GET['nonce'] ) ? sanitize_key( $_GET['nonce'] ) : null;
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( 'Invalid request.' );
		}

		// validate allowed user roles.
		$user          = wp_get_current_user();
		$allowed_roles = apply_filters(
			'wc_wallee_allowed_roles_to_download_documents',
			array(
				'administrator',
				'shop_manager',
			)
		);
		if ( ! array_intersect( $allowed_roles, $user->roles ) ) {
			wp_die( 'Access denied' );
		}

		$order_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : null;
		try {
			switch ( $action ) {
				case 'download_invoice':
					WC_Wallee_Download_Helper::download_invoice( $order_id );
					break;
				case 'download_packing':
					WC_Wallee_Download_Helper::download_packing_slip( $order_id );
					break;
			}
		} catch ( Exception $e ) {
			$message = $e->getMessage();
			$cleaned = preg_replace( '/^\[[A-Fa-f\d\-]+\] /', '', $message );
			wp_die( esc_html__( 'Could not fetch the document from wallee.', 'woo-wallee' ) . ' ' . esc_textarea( $cleaned ) );
		}

		$refer = isset( $_GET['refer'] );

		if ( 'edit' === $refer ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'post'   => $order_id,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				)
			);
		} else {
			wp_safe_redirect(
				add_query_arg(
					array(
						'post_type' => 'shop_order',
					),
					admin_url( 'edit.php' )
				)
			);
		}
		exit();
	}

	/**
	 * Check if request is PDF action.
	 *
	 * @return bool
	 */
	private static function is_download_request() {
		return ( isset( $_GET['post'] ) && isset( $_GET['wallee_admin'] ) && isset( $_GET['nonce'] ) );
	}
}

WC_Wallee_Admin_Document::init();
