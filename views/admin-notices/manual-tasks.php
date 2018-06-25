<?php 
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * wallee WooCommerce
 *
 * This WooCommerce plugin enables to process payments with wallee (https://www.wallee.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
?>

<div class="error notice">
	<p><?php
		echo _n('There is a manual task that needs your attention.', 'There are %s manual tasks that need your attention', $number_of_manual_tasks, 'woo-wallee');
		?>
    	</p>
	<p>
		<a href="<?php echo $manual_taks_url?>" target="_blank"><?php _e('View', 'woo-wallee')?></a>
	</p>
</div>