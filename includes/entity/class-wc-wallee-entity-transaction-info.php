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
 * This entity holds data about a transaction on the gateway.
 *
 * @method int get_id()
 * @method int get_transaction_id()
 * @method void set_transaction_id(int $id)
 * @method string get_state()
 * @method void set_state(string $state)
 * @method int get_space_id()
 * @method void set_space_id(int $id)
 * @method int get_space_view_id()
 * @method void set_space_view_id(int $id)
 * @method string get_language()
 * @method void set_language(string $language)
 * @method string get_currency()
 * @method void set_currency(string $currency)
 * @method float get_authorization_amount()
 * @method void set_authorization_amount(float $amount)
 * @method string get_image()
 * @method void set_image(string $image)
 * @method string get_image_base()
 * @method void set_image_base(string $image_base)
 * @method object get_labels()
 * @method void set_labels(map[string,string] $labels)
 * @method int get_payment_method_id()
 * @method void set_payment_method_id(int $id)
 * @method int get_connector_id()
 * @method void set_connector_id(int $id)
 * @method int get_order_id()
 * @method void set_order_id(int $id)
 * @method int get_order_mapping_id()
 * @method void set_order_mapping_id(int $id)
 * @method void set_failure_reason(map[string,string] $reasons)
 * @method string get_user_failure_message()
 * @method void set_user_failure_message(string $message)
 */
class WC_Wallee_Entity_Transaction_Info extends WC_Wallee_Entity_Abstract {

	/**
	 * Get field definition.
	 *
	 * @return array
	 */
	protected static function get_field_definition() {
		return array(
			'transaction_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'state' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'space_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'space_view_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'language' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'currency' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'authorization_amount' => WC_Wallee_Entity_Resource_Type::WALLEE_DECIMAL,
			'image' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'image_base' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'labels' => WC_Wallee_Entity_Resource_Type::WALLEE_OBJECT,
			'payment_method_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'connector_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'order_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'order_mapping_id' => WC_Wallee_Entity_Resource_Type::WALLEE_INTEGER,
			'failure_reason' => WC_Wallee_Entity_Resource_Type::WALLEE_OBJECT,
			'user_failure_message' => WC_Wallee_Entity_Resource_Type::WALLEE_STRING,
			'locked_at' => WC_Wallee_Entity_Resource_Type::WALLEE_DATETIME,
		);
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		return 'wallee_transaction_info';
	}

	/**
	 * Returns the translated failure reason.
	 *
	 * @param mixed $language language.
	 * @return string
	 */
	public function get_failure_reason( $language = null ) {
		$value = $this->get_value( 'failure_reason' );
		if ( empty( $value ) ) {
			return null;
		}
		return WC_Wallee_Helper::instance()->translate( $value, $language );
	}

	/**
	 * Load by order id.
	 *
	 * @param mixed $order_id order id.
	 * @return WC_Wallee_Entity_Transaction_Info
	 */
	public static function load_by_order_id( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(//phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $table WHERE order_id = %d",
				$order_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Load by transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 * @return WC_Wallee_Entity_Transaction_Info
	 */
	public static function load_by_transaction( $space_id, $transaction_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE space_id = %d AND transaction_id = %d",
				$space_id,
				$transaction_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}


	/**
	 * Load neweest by mapped order id.
	 *
	 * @param mixed $order_id order id.
	 * @return WC_Wallee_Entity_Transaction_Info
	 */
	public static function load_newest_by_mapped_order_id( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE order_mapping_id = %d ORDER BY id DESC",
				$order_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}
}
