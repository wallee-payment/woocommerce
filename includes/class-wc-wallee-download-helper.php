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
 * Class WC_Wallee_Download_Helper.
 * This class provides function to download documents from wallee
 *
 * @class WC_Wallee_Download_Helper
 */
class WC_Wallee_Download_Helper {

	/**
	 * Downloads the transaction's invoice PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_invoice( $order_id ) {
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && in_array(
			$transaction_info->get_state(),
			array(
				\Wallee\Sdk\Model\TransactionState::COMPLETED,
				\Wallee\Sdk\Model\TransactionState::FULFILL,
				\Wallee\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {

			$service = new \Wallee\Sdk\Service\TransactionService( WC_Wallee_Helper::instance()->get_api_client() );
			$document = $service->getInvoiceDocument( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Downloads the transaction's packing slip PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_packing_slip( $order_id ) {
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && $transaction_info->get_state() == \Wallee\Sdk\Model\TransactionState::FULFILL ) {

			$service = new \Wallee\Sdk\Service\TransactionService( WC_Wallee_Helper::instance()->get_api_client() );
			$document = $service->getPackingSlip( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Sends the data received by calling the given path to the browser and ends the execution of the script
	 *
	 * @param \Wallee\Sdk\Model\RenderedDocument $document document.
	 */
	public static function download( \Wallee\Sdk\Model\RenderedDocument $document ) {
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . esc_html( $document->getTitle() ) . '.pdf"' );
		header( 'Content-Description: ' . esc_html( $document->getTitle() ) );

		$data_safe = base64_decode( $document->getData() );
		echo $data_safe; // phpcs:ignore
		exit();
	}
}
