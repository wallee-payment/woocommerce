<?php
/**
 *
 * WC_Wallee_Service_Abstract Class
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
 * WC_Wallee_Service_Abstract Class.
 */
abstract class WC_Wallee_Service_Abstract {
	/**
	 * Instances.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Instance.
	 *
	 * @return static
	 */
	public static function instance() {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}
		return self::$instances[ $class ];
	}

	/**
	 * Converts a DatabaseTranslatedString into a serializable array.
	 *
	 * @param \Wallee\Sdk\Model\DatabaseTranslatedString $translated_string translated string.
	 * @return string[]
	 */
	protected function get_translations_array( \Wallee\Sdk\Model\DatabaseTranslatedString $translated_string ) {
		$translations = array();
		foreach ( $translated_string->getItems() as $item ) {
			$translation = $item->getTranslation();
			if ( ! empty( $translation ) ) {
				$translations[ $item->getLanguage() ] = $item->getTranslation();
			}
		}

		return $translations;
	}

	/**
	 * Returns the resource part of the resolved url
	 *
	 * @param String $resolved_url resolved url.
	 * @return string
	 */
	protected function get_resource_path( $resolved_url ) {
		if ( empty( $resolved_url ) ) {
			return $resolved_url;
		}
		$index = strpos( $resolved_url, 'resource/' );
		if ( false === $index ) {
			return $resolved_url;
		}
		return substr( $resolved_url, $index + strlen( 'resource/' ) );
	}

	/**
	 * Get resource base.
	 *
	 * @param mixed $resolved_url resolved url.
	 * @return mixed|string
	 */
	protected function get_resource_base( $resolved_url ) {
		if ( empty( $resolved_url ) ) {
			return $resolved_url;
		}
		$parts = parse_url( $resolved_url );
		return $parts['scheme'] . '://' . $parts['host'] . '/';
	}

	/**
	 * Returns the fraction digits for the given currency.
	 *
	 * @param string $currency_code currency code.
	 * @return number
	 */
	protected function get_currency_fraction_digits( $currency_code ) {
		return WC_Wallee_Helper::instance()->get_currency_fraction_digits( $currency_code );
	}

	/**
	 * Rounds the given amount to the currency's format.
	 *
	 * @param float  $amount amount.
	 * @param string $currency_code currency code.
	 * @return number
	 */
	protected function round_amount( $amount, $currency_code ) {
		return round( $amount, $this->get_currency_fraction_digits( $currency_code ) );
	}

	/**
	 * Creates and returns a new entity filter.
	 *
	 * @param string $field_name field name.
	 * @param mixed  $value value.
	 * @param string $operator operator.
	 * @return \Wallee\Sdk\Model\EntityQueryFilter
	 */
	protected function create_entity_filter( $field_name, $value, $operator = \Wallee\Sdk\Model\CriteriaOperator::EQUALS ) {
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType( \Wallee\Sdk\Model\EntityQueryFilterType::LEAF );
		$filter->setOperator( $operator );
		$filter->setFieldName( $field_name );
		$filter->setValue( $value );
		return $filter;
	}

	/**
	 * Creates and returns a new entity order by.
	 *
	 * @param string $field_name field name.
	 * @param string $sort_order sort order.
	 * @return \Wallee\Sdk\Model\EntityQueryOrderBy
	 */
	protected function create_entity_order_by( $field_name, $sort_order = \Wallee\Sdk\Model\EntityQueryOrderByType::DESC ) {
		$order_by = new \Wallee\Sdk\Model\EntityQueryOrderBy();
		$order_by->setFieldName( $field_name );
		$order_by->setSorting( $sort_order );
		return $order_by;
	}

	/**
	 * Changes the given string to have no more characters as specified.
	 *
	 * @param string $string string.
	 * @param int    $max_length max length.
	 * @return string
	 */
	protected function fix_length( $string, $max_length ) {
		return mb_substr( $string, 0, $max_length, 'UTF-8' );
	}

	/**
	 * Removes all non printable ASCII chars
	 *
	 * @param String $string string.
	 * @return $string
	 */
	protected function remove_non_ascii( $string ) {
		return preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $string );
	}
}
