<?php 
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
?>

<div class="error notice">
	<p><?php
		
		if ($number_of_manual_tasks == 1) {
			_e('There is a manual task that needs your attention.', 'woocommerce-wallee');
		}
		else {
			echo sprintf(__('There are %s manual tasks that need your attention', 'woocommerce-wallee'), $number_of_manual_tasks);
		}
		?>
    	</p>
	<p>
		<a href="<?php echo $manual_taks_url?>" target="_blank"><?php _e('View', 'woocommerce-wallee')?></a>
	</p>
</div>