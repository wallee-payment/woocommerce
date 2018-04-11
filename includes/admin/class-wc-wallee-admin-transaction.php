<?php
if (!defined('ABSPATH')) {
	exit();
}

/**
 * WC Wallee Admin Transaction class
 */
class WC_Wallee_Admin_Transaction {

	public static function init(){
		add_action('add_meta_boxes', array(
			__CLASS__,
			'add_meta_box' 
		), 40);
	}

	/**
	 * Add WC Meta boxes.
	 */
	public static function add_meta_box(){
		global $post;
		if ($post->post_type != 'shop_order') {
			return;
		}
		$order = WC_Order_Factory::get_order($post->ID);
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_Wallee_Gateway)) {
			return;
		}
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
		if ($transaction_info->get_id() == null) {
		    $transaction_info = WC_Wallee_Entity_Transaction_Info::load_newest_by_mapped_order_id($order->get_id());
		}
		if ($transaction_info->get_id() != null) {
			add_meta_box('woocommerce-order-wallee-transaction', 'wallee '.__('Transaction', 'woocommerc-wallee'), 
					array(
						__CLASS__,
						'output' 
					), 'shop_order', 'normal', 'default');
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output($post){
		global $post, $wpdb;
		
		$order = WC_Order_Factory::get_order($post->ID);
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_Wallee_Gateway)) {
			return;
		}
		$helper = WC_Wallee_Helper::instance();
		
		$transaction_info = WC_Wallee_Entity_Transaction_Info::load_by_order_id($order->get_id());
		if ($transaction_info->get_id() == null) {
		    $transaction_info = WC_Wallee_Entity_Transaction_Info::load_newest_by_mapped_order_id($order->get_id());
		    if ($transaction_info->get_id() == null) {
			 return;
		    }
		}
		$labels_by_group = self::get_grouped_charge_attempt_labels($transaction_info);
		?>
<div class="order-wallee-transaction-metabox wc-metaboxes-wrapper">
	<div class="wallee-transaction-data-column-container">
		<div class="wallee-transaction-column">
			<p>
				<strong><?php _e('General Details', 'woocommerce-wallee'); ?></strong>
			</p>
			<table class="form-list" style="margin-bottom: 20px;">
				<tbody>
					<tr>
						<td class="label"><label><?php _e('Payment Method', 'woocommerce-wallee') ?></label></td>
						<td class="value"><strong><?php echo $method->get_payment_method_configuration()->get_configuration_name() ?></strong>
						</td>
					</tr>
			<?php if (!empty($transaction_info->get_image())) :?>
				 <tr>
						<td class="label"></td>
						<td class="value"><img
							src="<?php echo $helper->get_resource_url($transaction_info->get_image(), $transaction_info->get_language(), $transaction_info->get_space_id(), $transaction_info->get_space_view_id()) ?>"
							width="50" /><br /></td>
					</tr>
			<?php endif; ?>
    			<tr>
						<td class="label"><label><?php  _e('Transaction State', 'woocommerce-wallee') ?></label></td>
						<td class="value"><strong><?php echo self::get_transaction_state($transaction_info);?></strong></td>
					</tr>
			
            <?php if ($transaction_info->get_failure_reason() != null):?>
            	<tr>
						<td class="label"><label><?php _e('Failure Reason', 'woocommerce-wallee') ?></label></td>
						<td class="value"><strong><?php echo $transaction_info->get_failure_reason()?></strong></td>
					</tr>
            <?php endif; ?>
            	<tr>
						<td class="label"><label><?php _e('Authorization Amount', 'woocommerce-wallee') ?></label></td>
						<td class="value"><strong><?php echo  wc_price( $transaction_info->get_authorization_amount(), array( 'currency' => $transaction_info->get_currency()) )?></strong></td>
					</tr>
					<tr>
						<td class="label"><label><?php _e('Transaction', 'woocommerce-wallee') ?></label></td>
						<td class="value"><strong> <a
								href="<?php echo self::get_transaction_url($transaction_info) ?>"
								target="_blank">
    					<?php _e('View', 'woocommerce-wallee') ?>
    				</a>
						</strong></td>
					</tr>

				</tbody>
			</table>
		</div>
		

<?php if (!empty($labels_by_group)) : ?>
	<?php foreach ($labels_by_group as $group) : ?>
	<div class="wallee-transaction-column">
			<div class="wallee-payment-label-container"
				id="wallee-payment-label-container-<?php echo $group['group']->getId() ?>">
				<p class="wallee-payment-label-group">
					<strong><?php echo $helper->translate($group['group']->getName()) ?></strong>
				</p>
				<table class="form-list" style="margin-bottom: 20px;">
					<tbody>
        			<?php foreach ($group['labels'] as $label) : ?>
                		<tr>
							<td class="label"><label><?php echo $helper->translate($label['descriptor']->getName()) ?></label></td>
							<td class="value"><strong><?php echo $label['value'] ?></strong></td>
						</tr>
    		    	<?php endforeach; ?>
    	    	</tbody>
				</table>
			</div>
		</div>
		
	<?php endforeach; ?>
<?php endif; ?>
	</div>
</div>
<?php
	}

	/**
	 * Returns the translated name of the transaction's state.
	 *
	 * @return string
	 */
	protected static function get_transaction_state(WC_Wallee_Entity_Transaction_Info $transaction_info){
		switch ($transaction_info->get_state()) {
		    case \Wallee\Sdk\Model\TransactionState::AUTHORIZED:
				return __('Authorized', 'woocommerce-wallee');
		    case \Wallee\Sdk\Model\TransactionState::COMPLETED:
				return __('Completed', 'woocommerce-wallee');
		    case \Wallee\Sdk\Model\TransactionState::CONFIRMED:
				return __('Confirmed', 'woocommerce-wallee');
		    case \Wallee\Sdk\Model\TransactionState::DECLINE:
				return __('Decline', 'woocommerce-wallee');
		    case \Wallee\Sdk\Model\TransactionState::FAILED:
				return __('Failed', 'woocommerce-wallee');
		    case \Wallee\Sdk\Model\TransactionState::FULFILL:
				return __('Fulfill', 'woocommerce-wallee');
			case \Wallee\Sdk\Model\TransactionState::PENDING:
				return __('Pending', 'woocommerce-wallee');
			case \Wallee\Sdk\Model\TransactionState::PROCESSING:
				return __('Processing', 'woocommerce-wallee');
			case \Wallee\Sdk\Model\TransactionState::VOIDED:
				return __('Voided', 'woocommerce-wallee');
			default:
				return __('Unknown State', 'woocommerce-wallee');
		}
	}

	/**
	 * Returns the URL to the transaction detail view in Wallee.
	 *
	 * @return string
	 */
	protected static function get_transaction_url(WC_Wallee_Entity_Transaction_Info $info){
	    return WC_Wallee_Helper::instance()->get_base_gateway_url() . '/s/' . $info->get_space_id() . '/payment/transaction/view/' .
				 $info->get_transaction_id();
	}

	/**
	 * Returns the charge attempt's labels by their groups.
	 *
	 * @return \Wallee\Sdk\Model\Label[]
	 */
	protected static function get_grouped_charge_attempt_labels(WC_Wallee_Entity_Transaction_Info $info){
		try {
		    $label_description_provider = WC_Wallee_Provider_Label_Description::instance();
		    $label_description_group_provider = WC_Wallee_Provider_Label_Description_Group::instance();
			
			$labels_by_group_id = array();
			foreach ($info->get_labels() as $descriptor_id => $value) {
				$descriptor = $label_description_provider->find($descriptor_id);
				if ($descriptor) {
					$labels_by_group_id[$descriptor->getGroup()][] = array(
						'descriptor' => $descriptor,
						'value' => $value 
					);
				}
			}
			
			$labels_by_group = array();
			foreach ($labels_by_group_id as $group_id => $labels) {
				$group = $label_description_group_provider->find($group_id);
				if ($group) {
					usort($labels, function ($a, $b){
						return $a['descriptor']->getWeight() - $b['descriptor']->getWeight();
					});
					$labels_by_group[] = array(
						'group' => $group,
						'labels' => $labels 
					);
				}
			}
			usort($labels_by_group, function ($a, $b){
				return $a['group']->getWeight() - $b['group']->getWeight();
			});
			
			return $labels_by_group;
		}
		catch (Exception $e) {
			return array();
		}
	}
}
WC_Wallee_Admin_Transaction::init();
