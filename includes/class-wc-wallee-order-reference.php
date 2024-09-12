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
 * Class WC_Wallee_Order_Reference.
 * This class handles the database setup and migration.
 *
 * @class WC_Wallee_Order_Reference
 */
class WC_Wallee_Order_Reference {
	const WALLEE_ORDER_ID = 'order_id';
	const WALLEE_ORDER_NUMBER = 'order_number';
}
