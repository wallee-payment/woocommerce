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

if ( class_exists( 'WP_CLI' ) && ! class_exists( 'WC_Wallee_Commands' ) ) {

    /**
     * Class WC_Wallee_Commands.
     * This class contains custom commands for wallee.
     *
     * @class WC_Wallee_Commands
     */
    class WC_Wallee_Commands {

        /**
         * Register commands.
         */
        public static function init() {
            WP_CLI::add_command(
                'wallee settings init',
                array(
                    __CLASS__,
                    'settings_init'
                )
            );
            WP_CLI::add_command(
                'wallee webhooks install',
                array(
                    __CLASS__,
                    'webhooks_install'
                )
            );
            WP_CLI::add_command(
                'wallee payment-methods sync',
                array(
                    __CLASS__,
                    'payment_methods_sync'
                )
            );
        }

        /**
         * Initialize wallee settings.
         * It doesn't reset settings to default, it sets default settings if they haven't been initialized yet.
         *
         * ## EXAMPLE
         *
         *     $ wp wallee settings init
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function settings_init( $args, $assoc_args ) {
            try {
                $default_settings = WC_Wallee_Helper::instance()->get_default_settings();
                foreach ( $default_settings as $setting => $value ) {
                    $current_setting = get_option( $setting, false );
                    if ( $current_setting === false ) {
                        update_option( $setting, $value );
                    }
                }
                WP_CLI::success( "Settings initialized." );
            } catch ( \Exception $e ) {
                WooCommerce_Wallee::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to initialize settings: " . $e->getMessage() );
            }
        }

        /**
         * Create webhook URL and webhook listeners in the portal for wallee.
         *
         * ## EXAMPLE
         *
         *     $ wp wallee webhooks install
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function webhooks_install( $args, $assoc_args ) {
            try {
                WC_Wallee_Helper::instance()->reset_api_client();
                WC_Wallee_Service_Webhook::instance()->install();
                WP_CLI::success( "Webhooks installed." );
            } catch ( \Exception $e ) {
                WooCommerce_Wallee::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to install webhooks: " . $e->getMessage() );
            }
        }

        /**
         * Synchronizes payment methods in the wallee from the portal.
         *
         * ## EXAMPLE
         *
         *     $ wp wallee payment-methods sync
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function payment_methods_sync( $args, $assoc_args ) {
            try {
                WC_Wallee_Helper::instance()->reset_api_client();
                WC_Wallee_Service_Method_Configuration::instance()->synchronize();
                WC_Wallee_Helper::instance()->delete_provider_transients();
                WP_CLI::success( "Payment methods synchronized." );
            } catch ( \Exception $e ) {
                WooCommerce_Wallee::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to synchronize payment methods: " . $e->getMessage() );
            }
        }
    }
}

WC_Wallee_Commands::init();