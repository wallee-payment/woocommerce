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
 * Defines the different resource types
 */
interface WC_Wallee_Entity_Resource_Type {
	const WALLEE_STRING = 'string';
	const WALLEE_DATETIME = 'datetime';
	const WALLEE_INTEGER = 'integer';
	const WALLEE_BOOLEAN = 'boolean';
	const WALLEE_OBJECT = 'object';
	const WALLEE_DECIMAL = 'decimal';
}
