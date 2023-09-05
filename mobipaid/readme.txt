=== Mobipaid ===

Contributors: mobipaid
Tags: credit card, mobipaid, google pay, apple pay, nedbank, payment method, payment gateway
Requires at least: 5.0
Tested up to: 6.1
Requires PHP: 7.0
Stable tag: 1.0.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Payments over multiple channels

== Description ==

= Because you want more than just a shopping cart experience =

* Take payments online and offline
* Use your preferred merchant service provider
* Promote directly on Facebook, LinkedIn and Twitter

= Changing the way you take card-not-present payments. =

Mobipaid is a single card-not-present payment platform that allows you to accept payments in a variety of ways: SEPA, Paypal, Credit/Debit Card, Nedbank EFT, google pay, apple pay and more. Add Mobipaid to your shopping cart for easy and secure payments during checkout, or use your Mobipaid portal to deliver payment requests to your customers using text messaging (SMS), email, social media, and QR codes.

Mobipaid is the best payment solution available for merchants who need payment flexibility, or if your business has grown beyond just eCommerce and the service you offer requires you to take payments anywhere, anytime.
 
== Features ==

* Accept payments via Mobipaid.
* Partial / Full refund.
* Subscription payments.
 
== Localization ==

* English (default) - always included.
* Arabic (ar)
* Danish (da_DK)
* German (de_DE)
* English(US) (en_US)
* Spanish(Spain) (es_ES)
* Finnish (fi)
* French (fr_FR)
* Indonesian (id_ID)
* Italian (it_IT)
* Japanese (ja)
* Korean (ko_KR)
* Dutch (nl_NL)
* Polish (pl_PL)
* Portuguese(Portugal) (pt_PT)
* Russian (ru_RU)
* Swedish (sv_SE)
* Turkish (tr_TR)
* Chinese(China) (zh_CN)

== Installation ==

Note: WooCommerce must be installed for this plugin to work.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of the Mobipaid plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Mobipaid” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating, and description. Most importantly, of course, you can install it by simply clicking "Install Now", then "Activate".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

1. Upload the entire `mobipaid` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Settings the plugin through the 'Plugins' menu in WordPress.

= Setup =

After installed the plugin, you need to go to plugin settings and input an access key that received from Mobipaid developer portal.
And please make sure Merchant has created POS Link with Reference number type Customer Input and sets avaiable currencies.

== Frequently Asked Questions ==

= Does this require an SSL certificate? =

Yes! In Live Mode, an SSL certificate must be installed on your site to use Mobipaid.

= Does this support both production mode and sandbox mode for testing? =

Yes, it does - production and sandbox mode is driven by the API Access keys you use.

= Where can I can get support? =

You can contact developer with this [link](https://mobipaid.com/contact/).

== Screenshots ==

1. Mobipaid banner.
2. The settings panel used to configure Mobipaid.
3. Checkout with Mobipaid.
4. Mobipaid payment page.

== Changelog ==

= 1.0.0 2020-04-06 =
* Initial release

= 1.0.1 - 2020-05-11 =
* change the way of get payment status.

= 1.0.2 - 2020-06-03 =
* change logo size.
* add readme for setup.
* change the way of get payment status.

= 1.0.3 - 2020-07-03 =
* add sku and unit price to payment page.

= 1.0.4 - 2020-12-30 =
* support compatibility wordpress 5.6 and woocommerce 4.8.0.

= 1.0.5 - 2021-06-24 =
* support compatibility wordpress 5.7.2 and woocommerce 5.4.1.
* support with woocommerce subscription plugin

= 1.0.6 - 2022-01-20 =
* fix order status for virtual product and download product

= 1.0.7 - 2022-03-26 =
* fix order status for gift card product

= 1.0.8 - 2022-11-05 =
* add sandbox development mode
* add auto create default pos link if not available
* support compatibility wordpress 6.1 and woocommerce 7.0.1.

= 1.0.9 - 2023-09-02 =
* support compatibility wordpress 6.3.1 and woocommerce 8.0.3.