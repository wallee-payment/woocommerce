=== WooCommerce Wallee ===
Contributors: customwebgmbh
Tags: woocommerce wallee, woocommerce, wallee, payment, e-commerce, webshop, psp, invoice, packing slips, pdf, customer invoice, processing
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 1.0.9
License: Apache 2
License URI: http://www.apache.org/licenses/LICENSE-2.0

Accept payments in WooCommerce with Wallee.

== Description ==

This plugin will add support for all Wallee payments methods to your WooCommerce webshop.
To use this extension, a wallee account is required. Sign up on [wallee.com](https://app-wallee.com/user/signup).

= Features = 

* Support for all available Wallee payment methods
* Edit title, description for every payment method
* Download invoices and packing slips
* Refunds
* WordPress Multisite support

== Installation ==

= Minimum Requirements =

* PHP version 5.6 or greater
* WordPress 4.4 or greater
* WooCommerce 3.0.0 or greater

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'Woocommerce Wallee'.
2. Activate the 'WooCommerce Wallee' plugin through the 'Plugins' menu in WordPress
3. Set your Wallee credentials at WooCommerce -> Settings -> Wallee (or use the *Settings* link in the Plugins overview)
4. You're done, the active payment methods should be visible in the checkout of your webshop.

= Manual installation =

1. Unpack the downloaded package.
2. Upload the directory 'woo-wallee' to the `/wp-content/plugins/` directory
3. Activate the 'WooCommerce Wallee' plugin through the 'Plugins' menu in WordPress
4. Set your Wallee credentials at WooCommerce -> Settings -> Wallee (or use the *Settings* link in the Plugins overview)
5. You're done, the active payment methods should be visible in the checkout of your webshop.

== Frequently Asked Questions ==

= Where can I find documentation? =

For help setting up and configuring WooCommerce Wallee please refer to the [wiki](https://github.com/wallee-payment/woo-wallee/wiki).

= Where can I report issues? =

If you have an issue please use the [issue tracker](https://github.com/wallee-payment/woo-wallee/issues).

== Changelog ==

= 1.0.9 - December 08, 2017 =

* Fix - Payment Method Image not updating
* Fix - Improved exception handling in checkout

= 1.0.8 - December 04, 2017 =

* Fix - Webhook not updated correctly

= 1.0.7 - December 01, 2017 =

* Fix - Order status transitions after manual task
* Dev - Updated to Wallee SDK 1.1.1

= 1.0.6 - November 22, 2017 =

* Fix - Order completion not updating correctly

= 1.0.5 - November 16, 2017 =

* Fix - Woocommerce order emails not send 
* Fix - Settings page not loading

= 1.0.4 - November 08, 2017 =

* Fix - Fatal Error in Widgets Page

= 1.0.3 - October 20, 2017 =

* Fix - Javascript issue with IE11
* Fix - Removed include of none existing CSS file
* Tweak - Improved language code resolving

= 1.0.2 - August 31, 2017 =

* Fix - Javascript Issues with Firefox/IE

= 1.0.1 - July 28, 2017 =

* Featue - Added document download buttons to order overview

= 1.0.0 - July 1, 2017 =

* Initial release.

 