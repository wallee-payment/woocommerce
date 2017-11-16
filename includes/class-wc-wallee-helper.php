<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * WC_Wallee_Helper Class.
 */
class WC_Wallee_Helper {
	private static $instance;
	private $api_client;

	private function __construct(){}

	/**
	 * 
	 * @return WC_Wallee_Helper
	 */
	public static function instance(){
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 
	 * @throws Exception
	 * @return \Wallee\Sdk\ApiClient
	 */
	public function get_api_client(){
		if ($this->api_client === null) {
			$user_id = get_option('wc_wallee_application_user_id');
			$user_key = get_option('wc_wallee_application_user_key');
			if (!empty($user_id) && !empty($user_key)) {
				$this->api_client = new \Wallee\Sdk\ApiClient($user_id, $user_key);
				$this->api_client->setBasePath($this->get_base_gateway_url() . '/api');
			}
			else {
				throw new Exception(__('The Wallee API user data are incomplete.', 'woocommerce-wallee'));
			}
		}
		return $this->api_client;
	}
	
	
	public function reset_api_client(){
		$this->api_client = null;
	}

	/**
	 * Returns the base URL to the gateway.
	 *
	 * @return string
	 */
	public function get_base_gateway_url(){
		return get_option('wc_wallee_base_gateway_url', 'https://app-wallee.com');
	}

	/**
	 * Returns the translation in the given language.
	 *
	 * @param array($language => $transaltion) $translated_string
	 * @param string $language
	 * @return string
	 */
	public function translate($translated_string, $language = null){
		if ($language == null) {
			$language = WC_Wallee_Helper::instance()->get_cleaned_locale();
		}
		if (isset($translated_string[$language])) {
			return $translated_string[$language];
		}
		
		try {
			/* @var WC_Wallee_Provider_Language $language_provider */
			$language_provider = WC_Wallee_Provider_Language::instance();
			$primary_language = $language_provider->find_primary($language);
			if (isset($translated_string[$primary_language->getIetfCode()])) {
				return $translated_string[$primary_language->getIetfCode()];
			}
		}
		catch (Exception $e) {
		}
		if (isset($translated_string['en-US'])) {
			return $translated_string['en-US'];
		}
		
		return null;
	}

	/**
	 * Returns the URL to a resource on Wallee in the given context (space, space view, language).
	 *
	 * @param string $path
	 * @param string $language
	 * @param int $spaceId
	 * @param int $spaceViewId
	 * @return string
	 */
	public function get_resource_url($path, $language = null, $space_id = null, $space_view_id = null){
		$url = $this->get_base_gateway_url();
		if (!empty($language)) {
			$url .= '/' . str_replace('_', '-', $language);
		}
		
		if (!empty($space_id)) {
			$url .= '/s/' . $space_id;
		}
		
		if (!empty($space_view_id)) {
			$url .= '/' . $space_view_id;
		}
		
		$url .= '/resource/' . $path;
		return $url;
	}

	/**
	 * Returns the fraction digits of the given currency.
	 *
	 * @param string $currency_code
	 * @return number
	 */
	public function get_currency_fraction_digits($currency_code){
		/* @var WC_Wallee_Provider_Currency $currency_provider */
		$currency_provider = WC_Wallee_Provider_Currency::instance();
		$currency = $currency_provider->find($currency_code);
		if ($currency) {
			return $currency->getFractionDigits();
		}
		else {
			return 2;
		}
	}

	/**
	 * Returns the total amount including tax of the given line items.
	 *
	 * @param \Wallee\Sdk\Model\LineItem[] $line_items
	 * @return float
	 */
	public function get_total_amount_including_tax(array $line_items){
		$sum = 0;
		foreach ($line_items as $line_item) {
			$sum += $line_item->getAmountIncludingTax();
		}
		return $sum;
	}

	/**
	 * Cleans the given line items by ensuring uniqueness and introducing adjustment line items if necessary.
	 *
	 * @param \Wallee\Sdk\Model\LineItemCreate[] $line_items
	 * @param float $expected_sum
	 * @param string $currency
	 * @return \Wallee\Sdk\Model\LineItemCreate[]
	 */
	public function cleanup_line_items(array $line_items, $expected_sum, $currency){
		$effective_sum = $this->round_amount($this->get_total_amount_including_tax($line_items), $currency);
		$diff = $this->round_amount($expected_sum, $currency) - $effective_sum;
		if ($diff != 0) {
			$line_item = new \Wallee\Sdk\Model\LineItemCreate();
			$line_item->setAmountIncludingTax($this->round_amount($diff, $currency));
			$line_item->setName(__('Rounding Adjustment', 'woocommerce-wallee'));
			$line_item->setQuantity(1);
			$line_item->setSku('rounding-adjustment');
			$line_item->setType($diff < 0 ? \Wallee\Sdk\Model\LineItem::TYPE_DISCOUNT : \Wallee\Sdk\Model\LineItem::TYPE_FEE);
			$line_item->setUniqueId('rounding-adjustment');
			$line_items[] = $line_item;
		}
		
		return $this->ensure_unique_ids($line_items);
	}

	/**
	 * Ensures uniqueness of the line items.
	 *
	 * @param \Wallee\Sdk\Model\LineItemCreate[] $line_items
	 * @return \Wallee\Sdk\Model\LineItemCreate[]
	 */
	public function ensure_unique_ids(array $line_items){
		$unique_ids = array();
		foreach ($line_items as $line_item) {
			$unique_id = $line_item->getUniqueId();
			if (empty($unique_id)) {
				$unique_id = preg_replace("/[^a-z0-9]/", '', strtolower($line_item->getSku()));
			}
			
			if (empty($unique_id)) {
				throw new Exception("There is an invoice item without unique id.");
			}
			
			if (isset($unique_ids[$unique_id])) {
				$backup = $unique_id;
				$unique_id = $unique_id . '_' . $unique_ids[$unique_id];
				$unique_ids[$backup]++;
			}
			else {
				$unique_ids[$unique_id] = 1;
			}
			
			$line_item->setUniqueId($unique_id);
		}
		
		return $line_items;
	}

	/**
	 * Returns the amount of the line item's reductions.
	 *
	 * @param \Wallee\Sdk\Model\LineItem[] $lineItems
	 * @param \Wallee\Sdk\Model\LineItemReduction[] $reductions
	 * @return float
	 */
	public function get_reduction_amount(array $line_items, array $reductions){
		$line_item_map = array();
		foreach ($line_items as $line_item) {
			$line_item_map[$line_item->getUniqueId()] = $line_item;
		}
		
		$amount = 0;
		foreach ($reductions as $reduction) {
			$line_item = $line_item_map[$reduction->getLineItemUniqueId()];
			$amount += $line_item->getUnitPriceIncludingTax() * $reduction->getQuantityReduction();
			$amount += $reduction->getUnitPriceReduction() * ($line_item->getQuantity() - $reduction->getQuantityReduction());
		}
		
		return $amount;
	}

	private function round_amount($amount, $currency_code){
		return round($amount, $this->get_currency_fraction_digits($currency_code));
	}

	public function get_current_cart_id(){
		$session_handler = WC()->session;
		$current_cart_id = $session_handler->get('wallee_current_cart_id', null);
		if ($current_cart_id === null) {
			$current_cart_id = hash('sha256', rand());
			$session_handler->set('wallee_current_cart_id', $current_cart_id);
		}
		return $current_cart_id;
	}

	public function destroy_current_cart_id(){
		$session_handler = WC()->session;
		$session_handler->set('wallee_current_cart_id', null);
	}

	public function maybe_restock_items_for_cancelled_order(WC_Order $order){
		$restocked = $order->get_meta('_wc_wallee_restocked', true);
		
		if (apply_filters('wc_wallee_cancelled_payment_restock', !$restocked, $order)) {
			$this->restock_items_for_cancelled_order($order);
			$order->add_meta_data('_wc_wallee_restocked', true, true);
			$order->save();
		}
	}

	protected function restock_items_for_cancelled_order(WC_Order $order){
		if ('yes' === get_option('woocommerce_manage_stock') && $order && apply_filters('wc_wallee_can_increase_order_stock', true, $order) &&
				 sizeof($order->get_items()) > 0) {
			foreach ($order->get_items() as $item) {
				if ($item->is_type('line_item') && ($product = $item->get_product()) && $product->managing_stock()) {
					$qty = apply_filters('woocommerce_order_item_quantity', $item->get_quantity(), $order, $item);
					$item_name = $product->get_formatted_name();
					$new_stock = wc_update_product_stock($product, $qty, 'increase');
					
					if (!is_wp_error($new_stock)) {
						/* translators: 1: item name 2: old stock quantity 3: new stock quantity */
						$order->add_order_note(
								sprintf(__('%1$s stock increased from %2$s to %3$s.', 'woocommerce-wallee'), $item_name, $new_stock - $qty, 
										$new_stock));
					}
				}
			}
			
			do_action('wc_wallee_restocked_order', $order);
		}
	}

	/**
	 * Create a lock to prevent concurrency.
	 *
	 * @param int $lockType
	 */
	public function lock_by_transaction_id($space_id, $transaction_id){
		global $wpdb;
		
		$data_array = array(
			'locked_at' => date("Y-m-d H:i:s") 
		);
		$type_array = array(
			'%s' 
		);
		$wpdb->query(
				$wpdb->prepare(
						"SELECT locked_at FROM " . $wpdb->prefix .
								 "woocommerce_wallee_transaction_info WHERE transaction_id = %d and space_id = %d FOR UPDATE", $transaction_id, 
								$space_id));
		
		$wpdb->update($wpdb->prefix . 'woocommerce_wallee_transaction_info', $data_array, 
				array(
					'transaction_id' => $transaction_id,
					'space_id' => $space_id 
				), $type_array, array(
					"%d",
					"%d" 
				));
	}
	
	public function get_cleaned_locale($useDefault = true){
		$languageString = get_locale();
		$languageString = str_replace('_','-', $languageString);
		$language = false;
		if(strlen($languageString) >= 5){
			//We assume it was a long ietf code, check if it exists
			$language = WC_Wallee_Provider_Language::instance()->find($languageString);
			//Get first part of IETF and try to resolve as ISO
			if(strpos($languageString, '-') !== false){
				$languageString = substr($languageString, 0, strpos($languageString, '-'));
			}
			
		}
		if(!$language){
			$language = WC_Wallee_Provider_Language::instance()->findByIsoCode(strtolower($languageString));
		}
		//We did not find anything, so fall back
		if(!$language){
			if($useDefault){
				return 'en-US';
			}
			return null;
		}
		return $language->getIetfCode();
		
	}
}