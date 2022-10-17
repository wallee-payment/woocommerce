<?php
/**
 *
 * WC_Wallee_Entity_Resource_Type Class
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (http://www.wallee.com/)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Defines the different resource types
 */
interface WC_Wallee_Entity_Resource_Type {
	const STRING = 'string';
	const DATETIME = 'datetime';
	const INTEGER = 'integer';
	const BOOLEAN = 'boolean';
	const OBJECT = 'object';
	const DECIMAL = 'decimal';
}
