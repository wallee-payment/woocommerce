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

?>

<div class="error notice notice-error">
	<p>
	<?php
	if ( 1 === $number_of_manual_tasks ) {
		esc_html_e( 'There is a manual task that needs your attention.', 'woo-wallee' );
	} else {
		/* translators: %s are replaced with int */
		echo esc_html( sprintf( _n( 'There is %s manual task that needs your attention.', 'There are %s manual tasks that need your attention', $number_of_manual_tasks, 'woo-wallee' ), $number_of_manual_tasks ) );
	}
	?>
		</p>
	<p>
		<a href="<?php echo esc_url( $manual_taks_url ); ?>" target="_blank"><?php esc_html_e( 'View', 'woo-wallee' ); ?></a>
	</p>
</div>
