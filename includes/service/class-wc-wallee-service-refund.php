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
 * This service provides functions to deal with Wallee refunds.
 */
class WC_Wallee_Service_Refund extends WC_Wallee_Service_Abstract {

	/**
	 * The refund API service.
	 *
	 * @var \Wallee\Sdk\Service\RefundService refund service.
	 */
	private $refund_service;

	/**
	 * Returns the refund by the given external id.
	 *
	 * @param int    $space_id space id.
	 * @param string $external_id external id.
	 * @return \Wallee\Sdk\Model\Refund
	 * @throws Exception Exception.
	 */
	public function get_refund_by_external_id( $space_id, $external_id ) {
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$query->setFilter( $this->create_entity_filter( 'externalId', $external_id ) );
		$query->setNumberOfEntities( 1 );
		$result = $this->get_refund_service()->search( $space_id, $query );
		if ( null != $result && ! empty( $result ) ) {
			return current( $result );
		} else {
			throw new Exception( 'The refund could not be found.' );
		}
	}

	/**
	 * Creates a refund request model for the given refund.
	 *
	 * @param WC_Order        $order order.
	 * @param WC_Order_Refund $refund refund.
	 * @return \Wallee\Sdk\Model\RefundCreate
	 * @throws Exception Exception.
	 */
	public function create( WC_Order $order, WC_Order_Refund $refund ) {

		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$transaction = WC_Wallee_Service_Transaction::instance()->get_transaction( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );

		$reductions = $this->get_reductions( $order, $refund );
		$reductions = $this->fix_reductions( $refund, $transaction, $reductions );

		$refund_create = new \Wallee\Sdk\Model\RefundCreate();
		$refund_create->setMerchantReference( $refund->get_id() );
		$refund_create->setExternalId( uniqid( $refund->get_id() . '-' ) );
		$refund_create->setReductions( $reductions );
		$refund_create->setTransaction( $transaction->getId() );
		$refund_create->setType( \Wallee\Sdk\Model\RefundType::MERCHANT_INITIATED_ONLINE );
		return $refund_create;
	}

	/**
	 * Returns the fixed line item reductions for the refund.
	 *
	 * If the amount of the given reductions does not match the refund's grand total, the amount to refund is distributed equally to the line items.
	 *
	 * @param WC_Order_Refund                                            $refund refund.
	 * @param \Wallee\Sdk\Model\Transaction               $transaction transaction.
	 * @param \Wallee\Sdk\Model\LineItemReductionCreate[] $reductions reductions.
	 * @return \Wallee\Sdk\Model\LineItemReductionCreate[]
	 * @throws Exception Exception.
	 */
	protected function fix_reductions( WC_Order_Refund $refund, \Wallee\Sdk\Model\Transaction $transaction, array $reductions ) {
		$base_line_items = $this->get_base_line_items( $transaction );

		$helper = WC_Wallee_Helper::instance();
		$reduction_amount = $helper->get_reduction_amount( $base_line_items, $reductions );
		$refund_total = $refund->get_total() * -1;
		$currency = $refund->get_currency();

		if ( $this->round_amount( $reduction_amount, $currency ) != $this->round_amount( $refund_total, $currency ) ) {
			$fixed_reductions = array();
			$base_amount = $helper->get_total_amount_including_tax( $base_line_items );
			$rate = $refund_total / $base_amount;
			foreach ( $base_line_items as $line_item ) {
				$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
				$reduction->setLineItemUniqueId( $line_item->getUniqueId() );
				$reduction->setQuantityReduction( 0 );
				$reduction->setUnitPriceReduction( $this->round_amount( $line_item->getAmountIncludingTax() * $rate / $line_item->getQuantity(), $currency ) );
				$fixed_reductions[] = $reduction;
			}
			$fixed_reduction_amount = $helper->get_reduction_amount( $base_line_items, $fixed_reductions );
			$rounding_difference = $refund_total - $fixed_reduction_amount;

			return $this->distribute_rounding_difference( $fixed_reductions, 0, $rounding_difference, $base_line_items, $currency );
		} else {
			return $reductions;
		}
	}

	/**
	 * Distribute rounding difference.
	 *
	 * @param \Wallee\Sdk\Model\LineItemReductionCreate[] $reductions reductions.
	 * @param int                                                        $index index.
	 * @param number                                                     $remainder remainder.
	 * @param \Wallee\Sdk\Model\LineItem[]                $base_line_items base line items.
	 * @param string                                                     $currency_code currency code.
	 * @throws Exception Exception.
	 * @return \Wallee\Sdk\Model\LineItemReductionCreate[]
	 */
	private function distribute_rounding_difference(
		array $reductions,
		$index,
		$remainder,
		array $base_line_items,
		$currency_code
	) {
		$digits = $this->get_currency_fraction_digits( $currency_code );

		$current_reduction = $reductions[ $index ];
		$delta = $remainder;
		$change = false;
		$positive = $delta > 0;
		$new_reduction = null;
		$applied_delta = null;
		if ( $current_reduction->getUnitPriceReduction() != 0 && $current_reduction->getQuantityReduction() === 0 ) {
			$line_item = $this->get_line_item_by_unique_id( $base_line_items, $current_reduction->getLineItemUniqueId() );
			if ( null != $line_item ) {
				while ( 0 != $delta ) {
					if ( $current_reduction->getUnitPriceReduction() < 0 ) {
						$new_reduction = $this->round_amount(
							$current_reduction->getUnitPriceReduction() - ( $delta / $line_item->getQuantity() ),
							$currency_code
						);
					} else {
						$new_reduction = $this->round_amount(
							$current_reduction->getUnitPriceReduction() + ( $delta / $line_item->getQuantity() ),
							$currency_code
						);
					}
					$applied_delta = ( $new_reduction - $current_reduction->getUnitPriceReduction() ) * $line_item->getQuantity();
					if ( round( $applied_delta, $digits + 1 ) <= round( $delta, $digits + 1 ) &&
						$this->compare_amounts( $new_reduction, $line_item->getUnitPriceIncludingTax(), $currency_code ) <= 0 ) {

							$change = true;
							break;
					}

					$new_delta = round( ( abs( $delta ) - pow( 0.1, $digits + 1 ) ) * ( $positive ? 1 : - 1 ), 10 );
					if ( ( $positive xor $new_delta > 0 ) && 0 != $delta ) {
						break;
					}
					$delta = $new_delta;
				}
			}
		}
		if ( $change ) {
			$current_reduction->setUnitPriceReduction( $new_reduction );
			$new_remainder = $remainder - $applied_delta;
		} else {
			$new_remainder = $remainder;
		}

		if ( 0 != $new_remainder && $index + 1 < count( $reductions ) ) {
			return $this->distribute_rounding_difference(
				$reductions,
				$index + 1,
				$new_remainder,
				$base_line_items,
				$currency_code
			);
		} elseif ( $new_remainder > pow( 0.1, $digits + 1 ) ) {
			throw new Exception( esc_html__( 'Could not distribute the rounding difference.', 'woo-wallee' ) );
		} else {
			return $reductions;
		}
	}

	/**
	 * Returns the line item reductions for the refund items.
	 *
	 * @param WC_Order        $order order.
	 * @param WC_Order_Refund $refund refund.
	 * @return \Wallee\Sdk\Model\LineItemReductionCreate[]
	 */
	protected function get_reductions( WC_Order $order, WC_Order_Refund $refund ) {
		$reductions = array();
		$currency = $order->get_currency();
		foreach ( $refund->get_items() as $item ) {

			$order_item = $order->get_item( $item->get_meta( '_refunded_item_id', true ) );

			$order_total = $order_item->get_total() + $order_item->get_total_tax();

			$order_quantity = 1;
			if ( $order_item->get_quantity() != 0 ) {
				$order_quantity = $order_item->get_quantity();
			}
			$order_unit_price = $order_total / $order_quantity;

			$refund_total = ( $item->get_total() + $item->get_total_tax() ) * -1;
			$refund_quantity = 1;
			if ( $item->get_quantity() != 0 ) {
				$refund_quantity = $item->get_quantity() * -1;
			}
			$refund_unit_price = $refund_total / $refund_quantity;

			$unique_id = $order_item->get_meta( '_wallee_unique_line_item_id', true );

			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId( $unique_id );

			// The merchant did not refund complete items, we have to adapt the unit price.
			if ( $this->round_amount( $order_unit_price, $currency ) != $this->round_amount( $refund_unit_price, $currency ) ) {
				$reduction->setQuantityReduction( 0 );
				$reduction->setUnitPriceReduction( $this->round_amount( $refund_total / $order_quantity, $currency ) );
			} else {
				$reduction->setQuantityReduction( $refund_quantity );
				$reduction->setUnitPriceReduction( 0 );
			}
			$reductions[] = $reduction;
		}
		foreach ( $refund->get_fees() as $fee ) {

			$order_fee = $order->get_item( $fee->get_meta( '_refunded_item_id', true ) );
			$unique_id = $order_fee->get_meta( '_wallee_unique_line_item_id', true );

			// Refunds amount are stored as negative values.
			$amount_including_tax = $fee->get_total() + $fee->get_total_tax();

			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId( $unique_id );
			$reduction->setQuantityReduction( 0 );
			$reduction->setUnitPriceReduction( $this->round_amount( $amount_including_tax * -1, $currency ) );
			$reductions[] = $reduction;
		}
		foreach ( $refund->get_shipping_methods() as $shipping ) {

			$order_shipping = $order->get_item( $shipping->get_meta( '_refunded_item_id', true ) );
			$unique_id = $order_shipping->get_meta( '_wallee_unique_line_item_id', true );

			// Refunds amount are stored as negativ values.
			$amount_including_tax = $shipping->get_total() + $shipping->get_total_tax();

			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId( $unique_id );
			$reduction->setQuantityReduction( 0 );
			$reduction->setUnitPriceReduction( $this->round_amount( $amount_including_tax * -1, $currency ) );
			$reductions[] = $reduction;
		}

		return $reductions;
	}


	/**
	 * Compare amounts.
	 *
	 * @param number $amount1 amount1.
	 * @param number $amount2 amount2.
	 * @param string $currency_code currency code.
	 * @return number
	 */
	private function compare_amounts( $amount1, $amount2, $currency_code ) {
		$rounded_amount1 = $this->round_amount( $amount1, $currency_code );
		$rounded_amount2 = $this->round_amount( $amount2, $currency_code );
		if ( $rounded_amount1 < $rounded_amount2 ) {
			return - 1;
		} elseif ( $rounded_amount1 > $rounded_amount2 ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Sends the refund to the gateway.
	 *
	 * @param int                                           $space_id space id.
	 * @param \Wallee\Sdk\Model\RefundCreate $refund refund.
	 * @return \Wallee\Sdk\Model\Refund
	 * @throws Exception Exception.
	 */
	public function refund( $space_id, \Wallee\Sdk\Model\RefundCreate $refund ) {
		return $this->get_refund_service()->refund( $space_id, $refund );
	}

	/**
	 * Returns the line items that are to be used to calculate the refund.
	 *
	 * This returns the line items of the latest refund if there is one or else of the completed transaction.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param \Wallee\Sdk\Model\Refund      $refund refund.
	 * @return \Wallee\Sdk\Model\LineItem[]
	 * @throws Exception Exception.
	 */
	protected function get_base_line_items( \Wallee\Sdk\Model\Transaction $transaction, \Wallee\Sdk\Model\Refund $refund = null ) {
		$last_successful_refund = $this->get_last_successful_refund( $transaction, $refund );
		if ( $last_successful_refund ) {
			return $last_successful_refund->getReducedLineItems();
		} else {
			return $this->get_transaction_invoice( $transaction )->getLineItems();
		}
	}


	/**
	 * Ger line item by unique id.
	 *
	 * @param array $line_items line items.
	 * @param mixed $unique_id unique id.
	 * @return mixed|null
	 */
	private function get_line_item_by_unique_id( array $line_items, $unique_id ) {
		foreach ( $line_items as $line_item ) {
			if ( $line_item->getUniqueId() == $unique_id ) {
				return $line_item;
			}
		}
		return null;
	}


	/**
	 * Returns the transaction invoice for the given transaction.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @return \Wallee\Sdk\Model\TransactionInvoice transaction invoice.
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 */
	protected function get_transaction_invoice( \Wallee\Sdk\Model\Transaction $transaction ) {
		$query = new \Wallee\Sdk\Model\EntityQuery();

		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType( \Wallee\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter(
					'state',
					\Wallee\Sdk\Model\TransactionInvoiceState::CANCELED,
					\Wallee\Sdk\Model\CriteriaOperator::NOT_EQUALS
				),
				$this->create_entity_filter( 'completion.lineItemVersion.transaction.id', $transaction->getId() ),
			)
		);
		$query->setFilter( $filter );

		$query->setNumberOfEntities( 1 );

		$invoice_service = new \Wallee\Sdk\Service\TransactionInvoiceService( WC_Wallee_Helper::instance()->get_api_client() );
		$result = $invoice_service->search( $transaction->getLinkedSpaceId(), $query );
		if ( ! empty( $result ) ) {
			return $result[0];
		} else {
			throw new Exception( 'The transaction invoice could not be found.' );
		}
	}


	/**
	 * Returns the last successful refund of the given transaction, excluding the given refund.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction transaction.
	 * @param \Wallee\Sdk\Model\Refund|null $refund refund.
	 * @return false|\Wallee\Sdk\Model\Refund
	 * @throws \Wallee\Sdk\ApiException ApiException.
	 */
	protected function get_last_successful_refund( \Wallee\Sdk\Model\Transaction $transaction, \Wallee\Sdk\Model\Refund $refund = null ) {
		$query = new \Wallee\Sdk\Model\EntityQuery();

		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType( \Wallee\Sdk\Model\EntityQueryFilterType::_AND );
		$filters = array(
			$this->create_entity_filter( 'state', \Wallee\Sdk\Model\RefundState::SUCCESSFUL ),
			$this->create_entity_filter( 'transaction.id', $transaction->getId() ),
		);
		if ( null != $refund ) {
			$filters[] = $this->create_entity_filter( 'id', $refund->getId(), \Wallee\Sdk\Model\CriteriaOperator::NOT_EQUALS );
		}

		$filter->setChildren( $filters );
		$query->setFilter( $filter );

		$query->setOrderBys(
			array(
				$this->create_entity_order_by( 'createdOn', \Wallee\Sdk\Model\EntityQueryOrderByType::DESC ),
			)
		);

		$query->setNumberOfEntities( 1 );

		$result = $this->get_refund_service()->search( $transaction->getLinkedSpaceId(), $query );
		if ( ! empty( $result ) ) {
			return $result[0];
		} else {
			return false;
		}
	}


	/**
	 * Returns the refund API service.
	 *
	 * @return \Wallee\Sdk\Service\RefundService
	 * @throws Exception Exception.
	 */
	protected function get_refund_service() {
		if ( null == $this->refund_service ) {
			$this->refund_service = new \Wallee\Sdk\Service\RefundService( WC_Wallee_Helper::instance()->get_api_client() );
		}

		return $this->refund_service;
	}
}
