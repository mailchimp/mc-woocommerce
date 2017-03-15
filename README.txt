=== MailChimp for WooCommerce ===
Contributors: ryanhungate, MailChimp
Tags: ecommerce,email,workflows,mailchimp
Donate link: https://mailchimp.com
Requires at least: 4.3
Tested up to: 4.6.1
Stable tag: 4.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your store to your MailChimp list to track sales, create targeted emails, send abandoned cart emails, and more.

== Description ==
- MailChimp for WooCommerce is a free plugin that connects your WooCommerce store with your MailChimp account.
- Your customers and their purchase data are automatically synced with MailChimp, so you can create targeted email campaigns based on buying behavior.

You’ll have the power to:

- Sync list and purchase data
- Create abandoned cart Automation workflows
- Send product recommendations
- Segment based on purchase history
- View your results and measure ROI

###A note for current WooCommerce integration users
This plugin supports our most powerful API 3.0 features, and is intended for users who have not yet integrated their WooCommerce stores with MailChimp.

You can run this new integration at the same time as your current WooCommerce integration for MailChimp. However, data from the older integration will display separately in subscriber profiles, and can’t be used with e-commerce features that require API 3.0.

== Installation ==
###Before You Start
Here are some things to know before you begin this process.

- This plugin requires you to have the [WooCommerce plugin](https://woocommerce.com/) already installed and activated in WordPress.
- Your hosting environment must meet [WooCommerce's minimum requirements](https://docs.woocommerce.com/document/server-requirements), including PHP 5.6 or greater.
- We recommend you use this plugin in a staging environment before installing it on production servers. To learn more about staging environments, [check out these related Wordpress plugins](https://wordpress.org/plugins/search.php?q=staging).
- MailChimp for WooCommerce syncs the customer’s first name, last name, email address, and orders.
- WooCommerce customers who haven't signed up for marketing emails will appear in the **Transactional** portion of your list, and cannot be exported. 

###Task Roadmap
You’ll need to do a few things to connect your WooCommerce store to MailChimp. 

- Download the plugin.
- Install the plugin on your WordPress Admin site.
- Connect the plugin with your MailChimp API Key.
- Configure your list settings to complete the data sync process.

For more information on settings and configuration, please visit our Knowledge Base: [http://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-woocommerce](http://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-woocommerce)

== Changelog ==
= 1.1.1 =
* Support fpr site url changes 
* Fix for WP Version 4.4 Compatibility issues
= 1.1.0 =
* Fix for persisting opt-in status
* Pass order URLs to MailChimp
* Pass partial refund status to MailChimp 

= 1.0.9 =
* billing and shipping address support for orders

= 1.0.8 =
* add landing_site, financial status and discount information for orders
* fix to support php 5.3

= 1.0.7 =
* add options to move, hide and change defaults for opt-in checkbox
* add ability to re-sync and display connection details
* support for subscriptions without orders
* additional small fixes and some internal logging removal

= 1.0.6 =
* fixed conflict with the plugin updater where the class could not be loaded correctly.
* fixed error validation for store name.
* fixed cross device abandoned cart url's

= 1.0.4 =
* fix for Abandoned Carts without cookies

= 1.0.3 =
* fixed cart posts on dollar amounts greater than 1000

= 1.0.2 =
* title correction for Product Variants
* added installation checks for WooCommerce and phone contact info
* support for free orders

= 1.0 =
* added is_synicng flag to prevent sends during backfill
* fix for conflicts with Gravity Forms Pro and installation issues
* skip all Amazon orders
* allow users to set opt-in for pre-existing customers during first sync
* add Plugin Updater

= 0.1.22 =
* flag quantity as 1 if the product does not manage inventory

= 0.1.21 =
* php version check to display warnings < 5.5

= 0.1.19 =
* fix campaign tracking on new orders

= 0.1.18 =
* check woocommerce dependency before activating the plugin

= 0.1.17 =
* fix php version syntax errors for array's

= 0.1.16 =
* fix namespace conflicts
* fix free order 0.00 issue
* fix product variant naming issue

= 0.1.15 =
* adding special MailChimp header to requests

= 0.1.14 =
* removing jquery dependencies

= 0.1.13 =
* fixing a number format issue on total_spent

= 0.1.12 =
* skipping orders placed through amazon due to seller agreements

= 0.1.11 =
* removed an extra debug log that was not needed

= 0.1.10 =
* altered debug logging and fixed store settings validation requirements

= 0.1.9 =
* using fallback to stream context during failed patch requests

= 0.1.8 =
* fixing http request header for larger patch requests

= 0.1.7 =
* fixing various bugs with the sync and product issues.

= 0.1.2 =
* fixed admin order update hook.
