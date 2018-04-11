=== WooCommerce wallee ===
Contributors: customwebgmbh
Tags: woocommerce wallee, woocommerce, wallee, payment, e-commerce, webshop, psp, invoice, packing slips, pdf, customer invoice, processing
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 1.1.0
License: Apache 2
License URI: http://www.apache.org/licenses/LICENSE-2.0

Accept payments in WooCommerce with wallee.

== Description ==

Website: [https://www.wallee.com](https://www.wallee.com)

The WooCommerce plugin offers an easy and convenient way to accept credit cards and all 
other payment methods listed below fast and securely. The payment forms will be fully integrated in your checkout 
and for credit cards there is no redirection to a payment page needed anymore. The pages are by default mobile optimized but 
the look and feel can be changed according the merchants needs. 

This plugin will add support for all wallee payments methods to your WooCommerce webshop.
To use this extension, a wallee account is required. Sign up on [wallee](https://app-wallee.com/user/signup).


= Features = 

wallee is an e-commerce hub that simplifies the payment process for merchants. We offer a standardized API to 
connect to your local payment service provider. New payment methods and additional payment processors can be 
added with the help of wallee just with a click.

However, wallee is not only a payment provider we are also a payment platform that connects your systems and simplifies your processes: 

* We are able to connect to your bank to simplify reconciliation. 
* We can connect to your Slack channel to report payment issues or simply send you an email
* We can take over the dunning process for you if you want to provide invoices
* We create the PDF invoices, packing slips and send them to your customers if you like (via E-Mail or postal services).
* We have a mobile SDKs for Android & iOS if you want to connect other channels
* Invoices and dunning processes can be created
* better conversion as integrated payment pages lead to a direct checkout in your shop without redirection
* reasonable packages for small, medium and bigger shops
* scale into new markets with a click. Payment methods can be activated easily

= PAYMENT METHODS = 

* Alipay Global
* Bank Transfer
* Credit / Debit Card
  * American Express
  * Bancontact
  * China Union Pay
  * Dankort
  * Diners Club
  * Discover
  * JCB
  * Maestro
  * MasterCard
  * PostFinance Card
  * Visa
  * Visa Electron
* Direct Debit (SEPA)
* Direct Debit (UK)
* EPS
* Giropay
* iDeal
* Invoice
* MasterPass
* Online Banking
  * Aktia
  * Ålandsbanken 
  * Belfius Direct Net
  * Citadele
  * Danske Bank
  * DNB Bank
  * Handelsbanken
  * ING HomePay
  * KBC
  * Krediidipank
  * LHV Pank
  * Nordea
  * OP-Pohjola
  * POP Pankki
  * PostFinance E-Finance
  * Säästöpankki
  * SEB
  * S-Pankki
  * Swedbank
* POLi
* Paybox
* Paydirekt
* Paylib
* PayPal
* paysafecard
* Przelewy24
* QIWI
* SOFORT Banking
* Skrill
* TWINT
* Tenpay
* Trustly

= SUPPORTED PAYMENT SERVICE PROVIDERS =

* Adyen
* Alipay Global
* Authipay
* Barclaycard ePDQ DirectLink
* Barclaycard ePDQ E-Commerce
* Braintree
* Computop
* ConCardis PayEngine DirectLink
* Concardis PayEngine E-Commerce
* Datatrans
* E-PAY
* EMS eCommerce
* FirstData Connect
* FirstData TeleCash
* Heidelpay Payment Platform
* ISR (orange inpayment slip)
* Ingenico ePayments DirectLink
* Ingencio ePayments E-Commerce
* InterCard
* KBC PayPage DirectLink
* KBC PayPage E-Commerce
* Mercanet 2.0
* mPAY24
* Open Payment Platform
* PayUnity.CONNECT
* Paydirekt
* Payone
* PayPal
* PostFinance E-Payment DirectLink
* PostFinance E-Payment E-Commerce
* PowerPay
* SOFORT Banking
* Saferpay
* Sage Pay
* Sips 2.0
* SlimPay
* Sogenactif 2.0
* Trustly

== Installation ==

= Minimum Requirements =

* PHP version 5.6 or greater
* WordPress 4.4 or greater
* WooCommerce 3.0.0 or greater

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'Woocommerce wallee'.
2. Activate the 'WooCommerce wallee' plugin through the 'Plugins' menu in WordPress
3. Set your wallee credentials at WooCommerce -> Settings -> wallee (or use the *Settings* link in the Plugins overview)
4. You're done, the active payment methods should be visible in the checkout of your webshop.

= Manual installation =

1. Unpack the downloaded package.
2. Upload the directory to the `/wp-content/plugins/` directory
3. Activate the 'WooCommerce wallee' plugin through the 'Plugins' menu in WordPress
4. Set your wallee credentials at WooCommerce -> Settings -> wallee (or use the *Settings* link in the Plugins overview)
5. You're done, the active payment methods should be visible in the checkout of your webshop.

== Frequently Asked Questions ==

= Where can I find documentation? =

For help setting up and configuring WooCommerce wallee please refer to the [wiki](https://github.com/wallee-payment/woo-wallee/wiki).

= Where can I report issues? =

If you have an issue please use the [issue tracker](https://github.com/wallee-payment/woo-wallee/issues).

== Changelog ==

 
= 1.1.0 - April 11, 2018 =

* Fix - Order Pay process
* Tweak - Additional Hooks to use with other plugins
* Dev - Internal Structure Changes
* Dev - Updated to wallee SDK 1.1.6
