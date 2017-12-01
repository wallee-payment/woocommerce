<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * This service provides functions to deal with Wallee refunds.
 */
class WC_Wallee_Service_Refund extends WC_Wallee_Service_Abstract {
	
	/**
	 * The refund API service.
	 *
	 * @var \Wallee\Sdk\Service\RefundService
	 */
	private $refund_service;

	/**
	 * Returns the refund by the given external id.
	 *
	 * @param int $space_id
	 * @param string $external_id
	 * @return \Wallee\Sdk\Model\Refund
	 */
	public function get_refund_by_external_id($space_id, $external_id){
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$query->setFilter($this->create_entity_filter('externalId', $external_id));
		$query->setNumberOfEntities(1);
		$result = $this->get_refund_service()->search($space_id, $query);
		if ($result != null && !empty($result)) {
			return current($result);
		}
		else {
			throw new Exception('The refund could not be found.');
		}
	}

	/**
	 * Creates a refund request model for the given creditmemo.
	 *
	 * @param WC_Order $order
	 * @param WC_Order_Refund $refund
	 * @return \Wallee\Sdk\Model\RefundCreate
	 */
	public function create(WC_Order $order, WC_Order_Refund $refund){
		$transaction = WC_Wallee_Service_Transaction::instance()->get_transaction($order->get_meta('_wallee_linked_space_id', true),
				$order->get_meta('_wallee_transaction_id', true));
		
		$reductions = $this->get_reductions($order, $refund);
		$reductions = $this->fix_reductions($refund, $transaction, $reductions);
		
		$wallee_refund = new \Wallee\Sdk\Model\RefundCreate();
		$wallee_refund->setExternalId(uniqid($refund->get_id() . '-'));
		$wallee_refund->setReductions($reductions);
		$wallee_refund->setTransaction($transaction->getId());
		$wallee_refund->setType(\Wallee\Sdk\Model\RefundType::MERCHANT_INITIATED_ONLINE);
		return $wallee_refund;
	}

	/**
	 * Returns the fixed line item reductions for the creditmemo.
	 *
	 * If the amount of the given reductions does not match the refund's grand total, the amount to refund is distributed equally to the line items.
	 *
	 * @param WC_Order_Refund $refund
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param \Wallee\Sdk\Model\LineItemReductionCreate[] $reductions
	 * @return \Wallee\Sdk\Model\LineItemReductionCreate[]
	 */
	protected function fix_reductions(WC_Order_Refund $refund, \Wallee\Sdk\Model\Transaction $transaction, array $reductions){
		$base_line_items = $this->get_base_line_items($transaction);
		
		$helper = WC_Wallee_Helper::instance();
		$reduction_amount = $helper->get_reduction_amount($base_line_items, $reductions);
		$refund_total = $refund->get_total() * -1;
		
		if (wc_format_decimal($reduction_amount) != wc_format_decimal($refund_total)) {
			$fixed_reductions = array();
			$base_amount = $helper->get_total_amount_including_tax($base_line_items);
			$rate = $refund_total / $base_amount;
			foreach ($base_line_items as $line_item) {
				$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
				$reduction->setLineItemUniqueId($line_item->getUniqueId());
				$reduction->setQuantityReduction(0);
				$reduction->setUnitPriceReduction(round($line_item->getAmountIncludingTax() * $rate / $line_item->getQuantity(), 8));
				$fixed_reductions[] = $reduction;
			}
			
			return $fixed_reductions;
		}
		else {
			return $reductions;
		}
	}

	/**
	 * Returns the line item reductions for the creditmemo's items.
	 *
	 * @param WC_Order $order 
	 * @param WC_Order_Refund $refund
	 * @return \Wallee\Sdk\Model\LineItemReductionCreate[]
	 */
	protected function get_reductions(WC_Order $order, WC_Order_Refund $refund){
		$reductions = array();
		foreach ($refund->get_items() as $item_id => $item) {
			
			$order_item = $order->get_item($item->get_meta('_refunded_item_id', true));
			
			$order_total = $order_item->get_total() + $order_item->get_total_tax();
			
			$order_quantity = 1;
			if ($order_item->get_quantity() != 0) {
				$order_quantity = $order_item->get_quantity();
			}
			$order_unit_price = $order_total / $order_quantity;
			
			$refund_total = ($item->get_total() + $item->get_total_tax()) * -1;
			$refund_quantity = 1;
			if ($item->get_quantity() != 0) {
				$refund_quantity = $item->get_quantity() * -1;
			}
			$refund_unit_price = $refund_total / $refund_quantity;
			
			$unique_id = $order_item->get_meta('_wallee_unique_line_item_id', true);
			
			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId($unique_id);
			
			//The merchant did not refund complete items, we have to adapt the unit price
			if (wc_format_decimal($order_unit_price) != wc_format_decimal($refund_unit_price)) {
				$reduction->setQuantityReduction(0);
				$reduction->setUnitPriceReduction(round($refund_total / $order_quantity, 8));
			}
			else {
				$reduction->setQuantityReduction($refund_quantity);
				$reduction->setUnitPriceReduction(0);
			}
			$reductions[] = $reduction;
		}
		foreach ($refund->get_fees() as $fee_id => $fee) {
			
			$order_fee = $order->get_item($fee->get_meta('_refunded_item_id', true));
			$unique_id = $order_fee->get_meta('_wallee_unique_line_item_id', true);
			
			//Refunds amount are stored as negativ values
			$amount_including_tax = $fee->get_total() + $fee->get_total_tax();
			
			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId($unique_id);
			$reduction->setQuantityReduction(0);
			$reduction->setUnitPriceReduction($amount_including_tax * -1);
			$reductions[] = $reduction;
		}
		foreach ($refund->get_shipping_methods() as $shipping_id => $shipping) {
			
			$order_shipping = $order->get_item($shipping->get_meta('_refunded_item_id', true));
			$unique_id = $order_shipping->get_meta('_wallee_unique_line_item_id', true);
			
			//Refunds amount are stored as negativ values
			$amount_including_tax = $shipping->get_total() + $shipping->get_total_tax();
			
			$reduction = new \Wallee\Sdk\Model\LineItemReductionCreate();
			$reduction->setLineItemUniqueId($unique_id);
			$reduction->setQuantityReduction(0);
			$reduction->setUnitPriceReduction($amount_including_tax * -1);
			$reductions[] = $reduction;
		}
		
		return $reductions;
	}

	/**
	 * Sends the refund to the gateway.
	 *
	 * @param int $spaceId
	 * @param \Wallee\Sdk\Model\RefundCreate $refund
	 * @return \Wallee\Sdk\Model\Refund
	 */
	public function refund($spaceId, \Wallee\Sdk\Model\RefundCreate $refund){
		return $this->get_refund_service()->refund($spaceId, $refund);
	}

	/**
	 * Returns the line items that are to be used to calculate the refund.
	 *
	 * This returns the line items of the latest refund if there is one or else of the completed transaction.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param \Wallee\Sdk\Model\Refund $refund
	 * @return \Wallee\Sdk\Model\LineItem[]
	 */
	protected function get_base_line_items(\Wallee\Sdk\Model\Transaction $transaction, \Wallee\Sdk\Model\Refund $refund = null){
		$last_successful_refund = $this->get_last_successful_refund($transaction, $refund);
		if ($last_successful_refund) {
			return $last_successful_refund->getReducedLineItems();
		}
		else {
			return $this->get_transaction_invoice($transaction)->getLineItems();
		}
	}

	/**
	 * Returns the transaction invoice for the given transaction.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @throws Exception
	 * @return \Wallee\Sdk\Model\TransactionInvoice
	 */
	protected function get_transaction_invoice(\Wallee\Sdk\Model\Transaction $transaction){
		$query = new \Wallee\Sdk\Model\EntityQuery();
		
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType(\Wallee\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
					$this->create_entity_filter('state', \Wallee\Sdk\Model\TransactionInvoiceState::CANCELED,
							\Wallee\Sdk\Model\CriteriaOperator::NOT_EQUALS),
					$this->create_entity_filter('completion.lineItemVersion.transaction.id', $transaction->getId()) 
				));
		$query->setFilter($filter);
		
		$query->setNumberOfEntities(1);
		
		$invoice_service = new \Wallee\Sdk\Service\TransactionInvoiceService(WC_Wallee_Helper::instance()->get_api_client());
		$result = $invoice_service->search($transaction->getLinkedSpaceId(), $query);
		if (!empty($result)) {
			return $result[0];
		}
		else {
			throw new Exception('The transaction invoice could not be found.');
		}
	}

	/**
	 * Returns the last successful refund of the given transaction, excluding the given refund.
	 *
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param \Wallee\Sdk\Model\Refund $refund
	 * @return \Wallee\Sdk\Model\Refund
	 */
	protected function get_last_successful_refund(\Wallee\Sdk\Model\Transaction $transaction, \Wallee\Sdk\Model\Refund $refund = null){
		$query = new \Wallee\Sdk\Model\EntityQuery();
		
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType(\Wallee\Sdk\Model\EntityQueryFilterType::_AND);
		$filters = array(
			$this->create_entity_filter('state', \Wallee\Sdk\Model\RefundState::SUCCESSFUL),
			$this->create_entity_filter('transaction.id', $transaction->getId()) 
		);
		if ($refund != null) {
			$filters[] = $this->create_entity_filter('id', $refund->getId(), \Wallee\Sdk\Model\CriteriaOperator::NOT_EQUALS);
		}
		
		$filter->setChildren($filters);
		$query->setFilter($filter);
		
		$query->setOrderBys(array(
			$this->create_entity_order_by('createdOn', \Wallee\Sdk\Model\EntityQueryOrderByType::DESC) 
		));
		
		$query->setNumberOfEntities(1);
		
		$result = $this->get_refund_service()->search($transaction->getLinkedSpaceId(), $query);
		if (!empty($result)) {
			return $result[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Returns the refund API service.
	 *
	 * @return \Wallee\Sdk\Service\RefundService
	 */
	protected function get_refund_service(){
		if ($this->refund_service == null) {
			$this->refund_service = new \Wallee\Sdk\Service\RefundService(WC_Wallee_Helper::instance()->get_api_client());
		}
		
		return $this->refund_service;
	}
}