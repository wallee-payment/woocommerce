=== WooCommerce Wallee ===
Contributors: customwebgmbh
Tags: woocommerce wallee, woocommerce, wallee, payment, e-commerce, webshop, psp, invoice, packing slips, pdf, customer invoice, processing
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 1.0.6
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

For help setting up and configuring WooCommerce Wallee please refer to the [wiki](https://github.com/wallee-payment/woocommerce/wiki).

= Where can I report issues? =

If you have an issue please use the [issue tracker](https://github.com/wallee-payment/woocommerce/issues).

== Changelog ==

= 1.0.6 - November 22, 2017 =

- Fixes issue with order completion

= 1.0.5 - November 16, 2017 =

- Fixed woocommerce order emails not send 
- Fixes issue loading the settings page

= 1.0.4 - November 08, 2017 =

- Fixed issue in wordpress admin

= 1.0.3 - October 20, 2017 =

- Fixed Javascript issue with IE11
- Improved language code resolving
- Removed include of none existing CSS file

= 1.0.2 - August 31, 2017 =

- Fixed Javascript issues with Firefox/IE

= 1.0.1 - July 28, 2017 =

- Added document download buttons to order overview

= 1.0.0 - July 1, 2017 =

- Initial release.

 