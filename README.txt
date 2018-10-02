=== Mailchimp for WooCommerce ===
Contributors: ryanhungate, Mailchimp
Tags: ecommerce,email,workflows,mailchimp
Donate link: https://mailchimp.com
Requires at least: 4.3
Tested up to: 4.9.8
Stable tag: 2.1.10
Requires PHP: 5.6
WC tested up to: 3.4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Connect your store to your Mailchimp list to track sales, create targeted emails, send abandoned cart emails, and more.

== Description ==
Join the 17 million customers who use Mailchimp, the world's largest marketing automation platform, to develop their e-commerce marketing strategy. With the official Mailchimp for WooCommerce integration, your customers and their purchase data are automatically synced with your Mailchimp account, making it easy to send targeted campaigns, automatically follow up with customers post-purchase, recommend products, recover abandoned carts, and measure the ROI of your marketing efforts. And it's completely free.
With Mailchimp for WooCommerce, you’ll have the power to:
- Sync list and purchase data.
- Set up marketing automations to remind customers about items they left in their cart or viewed on your site, win back lapsed customers, and follow up post-purchase. (Now available for free accounts!)
- Showcase product recommendations.
- Track and segment customers based on purchase history and purchase frequency.
- View detailed data on your marketing performance in your Mailchimp Dashboard.
- Find new customers, connect with current ones, and drive them all to your website with [Facebook](https://mailchimp.com/features/facebook-ads/) and [Instagram](https://mailchimp.com/features/instagram-ads/) ads. Then, set up [Google remarketing](https://mailchimp.com/features/google-remarketing-ads/) ads to turn your site visitors into shoppers.
- Automatically embed a pop-up form that converts your website visitors to subscribers.
- Add discount codes created in WooCommerce to your emails and automations with a Promo Code content block
- Create beautiful landing pages that make it easy to highlight your products, promote a sale or giveaway, and grow your list.
###Important Notes
This plugin supports our most powerful API 3.0 features, and is intended for users who have not yet integrated their WooCommerce stores with Mailchimp.
You can run this new integration at the same time as your current WooCommerce integration for Mailchimp. However, data from the older integration will display separately in subscriber profiles, and can’t be used with e-commerce features that require API 3.0.
WordPress.com compatibility is limited to Business tier users only.
At this time, the synchronization of product categories from WooCommerce to Mailchimp is not supported.
== Installation ==
###Before You Start
Here are some things to know before you begin this process.
- This plugin requires you to have the [WooCommerce plugin](https://woocommerce.com/) already installed and activated in WordPress.
- Your hosting environment must meet [WooCommerce's minimum requirements](https://docs.woocommerce.com/document/server-requirements), including PHP 7.0 or greater.
- We recommend you use this plugin in a staging environment before installing it on production servers. To learn more about staging environments, [check out these related Wordpress plugins](https://wordpress.org/plugins/search.php?q=staging).
- Mailchimp for WooCommerce syncs the customer’s first name, last name, email address, and orders.
- WooCommerce customers who haven't signed up for marketing emails will appear in the **Transactional** portion of your list, and cannot be exported.
###Task Roadmap
You’ll need to do a few things to connect your WooCommerce store to Mailchimp.
- Download the plugin.
- Install the plugin on your WordPress Admin site.
- Connect the plugin with your Mailchimp API Key.
- Configure your list settings to complete the data sync process.
###Advanced Queue Setup In CLI mode
To optimize the performance of your Mailchimp integration - it is recommended that you run the queue in CLI mode.
First define a constant in your config file
    `define('DISABLE_WP_HTTP_WORKER', true);`
You have 2 options to run this process:
1. On a cron schedule every minute:
    `* * * * * /usr/bin/wp --url=http://yourdomain.com --path=/full/path/to/install/ queue listen`
2. Using a process manager like Monit or Supervisord:
    `/usr/bin/wp --url=http://yourdomain.com --path=/full/path/to/install/ queue listen`
### Optional on-demand queue processing
If you would like to turn off the background queue processing and handle jobs "on-demand" you can do so by adding a constant in your wp-config.php file:
    `define('MAILCHIMP_DISABLE_QUEUE', true);`
### Multi-site Setups
The Mailchimp for WooCommerce supports Wordpress Multi Sites and below are a few things to note.
- Each site that has the plugin installed is a separate connection to Mailchimp.
- Deactivating - disables the plugin from sending data to Mailchimp. Upon reactivating the plugins original setup details will be intact. Deleting is necessary to connect a different  Mailchimp list with WooCommerce.
- Deleting removes the connection between Mailchimp and WooCommerce, and uninstalls the plugin from your site.
Refer to the Wordpress Codex for more information about [Multisite Network Administration](https://codex.wordpress.org/Multisite_Network_Administration)
== Changelog ==
= 2.1.10 =
* skip product when no variant can be loaded
* better validation for the view order url
* Add Initial sync label on Sync Tab
* Multisite Delete and deactivate improvements
* Mailchimp Order Notification issues support for downloadable and virtual products
* http worker lock improvement 
* Add documentation about Multisite setup 
* Add documentaiton for on-demand syncing
= 2.1.9 =
* Improved UI feedback when API key is invalid
* Add documentation about product categories not being supported.
* Fix order count and order total with guest accounts.
= 2.1.8 =
* GDPR compliance
* changed css class on checkbox for registration issues
* added translation for newsletter checkbox text
* only show newsletter checkbox when plugin is fully configured
* fixed various sign up form conflicts with newsletter registration button
* added link to privacy policy
* force javascript into footer for performance gains
* fix logged in user abandoned cart tracking
* WPML support
* uninstall - reinstall clean ups
= 2.1.7 =
* fixed autoloader filepath for queue command
= 2.1.6 =
* moved to an autoloader for performance enhancement
* flush database tables on un-installation to assist with stale records in the queue
* turn on standard debugging by default to help troubleshoot issues
* moved the plugin button to the left main navigation
* allow store owners to select the image size being used for products
* fix paypal new order bug where it did not send on initial placement
* add additional configuration success checks for the plugin being configured before pushing any jobs into the queue
* fix the multisite network activation issue
* hide the opt in checkbox for already logged in customers that were previously subscribed
* miscellaneous UI enhancements
= 2.1.5 =
* is_configured filters applied before certain jobs were firing and failing.
= 2.1.5 =
* added support for Polish (zloty - zł) and Moldovan Leu currencies
* update currency code for Belarusian Rouble
* queue performance enhancement
= 2.1.4 =
* updated wordpress compatibility
* updated sync details tab to show more informative stats
* queue job processing performance enhancement
* added an integrity check for queued jobs that were not getting processed
= 2.1.3 =
* Fix subscriber status for repeat transactional customers to stay transactional.
* Remove shipping and billing address requirements for order submission.
* Do not unsubscribe someone who has previously subscribed when unchecking the newsletter sign up box.
* Update newsletter checkbox style to be consistent with WooCommerce styles.
* Make sure WooCommerce plugin is running before running any plugin code.
* Fix compatibility issue with WP-Cron
= 2.1.2 =
* Fix store deletion on plugin deactivation
* Correct shipping name is now used on order notifications.
* Admin orders are now handled appropriately.
* Skip incomplete or cancelled orders from being submitted when new.
* fix hidden or inactive products from being recommended.
= 2.1.1 =
* To address performance issues previously reported, we've changed the action hook of "woocommerce_cart_updated" to use a filter "woocommerce_update_cart_action_cart_updated"
= 2.1.0 =
* Added Promo Code support.
= 2.0.2 =
* Added new logs feature to help troubleshoot isolated sync and data feed issues.
* Fixed bug with setting customers as Transactional during checkout if they had already opted in previously.
* Fixed bug where abandoned cart automation still fired after a customer completed an order.
= 2.0.1 =
* Added support for "Connected Site" scripts.
* Made physical address a required field for store setup.
* Fixed order, cart timestamps to begin using UTC.
= 2.0 =
* Support WooComerce 3.0
* Support for manually uploaded WooCommerce
* Fix for sync issues
* Fix for guest orders sync issue
* Remove Mailchimp debug logger
= 1.1.1 =
* Support for site url changes
* Fix for WP Version 4.4 compatibility issues
= 1.1.0 =
* Fix for persisting opt-in status
* Pass order URLs to Mailchimp
* Pass partial refund status to Mailchimp
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
* adding special Mailchimp header to requests
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
