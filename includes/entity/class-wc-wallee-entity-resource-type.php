<?php
if (!defined('ABSPATH')) {
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