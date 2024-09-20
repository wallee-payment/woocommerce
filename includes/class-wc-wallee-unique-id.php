<?php
/**
 *
 * WC_Wallee_Unique_Id Class
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
 * Class WC_Wallee_Unique_Id.
 *
 * @class WC_Wallee_Unique_Id
 */
/**
 * This class handles the required unique ids
 */
class WC_Wallee_Unique_Id {

	/**
	 * Register item id functions hooks
	 */
	public static function init() {
		add_filter(
			'woocommerce_checkout_create_order_line_item',
			array(
				__CLASS__,
				'copy_unqiue_id_to_order_item',
			),
			10,
			4
		);
		add_filter(
			'woocommerce_checkout_create_order_fee_item',
			array(
				__CLASS__,
				'copy_unqiue_id_to_order_fee',
			),
			10,
			4
		);
		add_filter(
			'woocommerce_checkout_create_order_shipping_item',
			array(
				__CLASS__,
				'copy_unqiue_id_to_order_shipping',
			),
			10,
			4
		);
	}

	/**
	 * Copy unique id to order item
	 *
	 * @return mixed uuid
	 */
	public static function get_uuid() {
		$data = openssl_random_pseudo_bytes( 16 );
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100.
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10.

		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * Copy unique id to order item
	 *
	 * @param WC_Order_Item_Product $item item.
	 * @param mixed                 $cart_item_key cart_item_key.
	 * @param mixed                 $values values.
	 * @param WC_Order              $order order.
	 *
	 * @return WC_Order_Item_Product $item item
	 */
	public static function copy_unqiue_id_to_order_item( WC_Order_Item_Product $item, $cart_item_key, $values, WC_Order $order = null ) {
		// We do not use the cart_item_key as it is deprecated.
		$item->add_meta_data( '_wallee_unique_line_item_id', self::get_uuid(), true );
		return $item;
	}

	/**
	 * Copy unique id to order shipping
	 *
	 * @param WC_Order_Item_Shipping $item item.
	 * @param mixed                  $package_key package_key.
	 * @param mixed                  $package package.
	 * @param WC_Order               $order order.
	 *
	 * @return WC_Order_Item_Shipping $item item
	 */
	public static function copy_unqiue_id_to_order_shipping( WC_Order_Item_Shipping $item, $package_key, $package, WC_Order $order = null ) {
		$item->add_meta_data( '_wallee_unique_line_item_id', self::get_uuid(), true );
		return $item;
	}

	/**
	 * Copy unique id to order fee
	 *
	 * @param WC_Order_Item_Fee $item item.
	 * @param mixed             $fee_key fee_key.
	 * @param mixed             $fee fee.
	 * @param WC_Order          $order order.
	 *
	 * @return WC_Order_Item_Shipping $item item
	 */
	public static function copy_unqiue_id_to_order_fee( WC_Order_Item_Fee $item, $fee_key, $fee, WC_Order $order = null ) {
		$unique_id = null;
		if ( $fee->amount < 0 ) {
			$unique_id = 'discount-' . $fee->id;
		} else {
			$unique_id = 'fee-' . $fee->id;
		}
		$item->add_meta_data( '_wallee_unique_line_item_id', $unique_id, true );
		return $item;
	}
}
WC_Wallee_Unique_Id::init();
