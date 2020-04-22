=== Mailchimp for WooCommerce ===
Contributors: ryanhungate, Mailchimp
Tags: ecommerce,email,workflows,mailchimp
Donate link: https://mailchimp.com
Requires at least: 4.9
Tested up to: 5.4
Stable tag: 2.3.6
Requires PHP: 7.0
WC requires at least: 3.5
WC tested up to: 4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Connect your store to your Mailchimp audience to track sales, create targeted emails, send abandoned cart emails, and more.

== Description ==
Join the 17 million customers who use Mailchimp, the world's largest marketing automation platform, to develop their e-commerce marketing strategy. With the official Mailchimp for WooCommerce integration, your customers and their purchase data are automatically synced with your Mailchimp account, making it easy to send targeted campaigns, automatically follow up with customers post-purchase, recommend products, recover abandoned carts, and measure the ROI of your marketing efforts. And it's completely free.
With Mailchimp for WooCommerce, you’ll have the power to:
- Sync audience and purchase data.
- Set up marketing automations to remind customers about items they left in their cart or viewed on your site, win back lapsed customers, and follow up post-purchase. (Now available for free accounts!)
- Showcase product recommendations.
- Track and segment customers based on purchase history and purchase frequency.
- View detailed data on your marketing performance in your Mailchimp Dashboard.
- Find new customers, connect with current ones, and drive them all to your website with [Facebook](https://mailchimp.com/features/facebook-ads/) and [Instagram](https://mailchimp.com/features/instagram-ads/) ads. Then, set up [Google remarketing](https://mailchimp.com/features/google-remarketing-ads/) ads to turn your site visitors into shoppers.
- Automatically embed a pop-up form that converts your website visitors to subscribers.
- Add discount codes created in WooCommerce to your emails and automations with a Promo Code content block
- Create beautiful landing pages that make it easy to highlight your products, promote a sale or giveaway, and grow your audience.
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
- WooCommerce customers who haven't signed up for marketing emails will appear in the **Transactional** portion of your audience, and cannot be exported.
###Task Roadmap
You’ll need to do a few things to connect your WooCommerce store to Mailchimp.
- Download the plugin.
- Install the plugin on your WordPress Admin site.
- Connect securely to your Mailchimp account via secure OAuth pop-up window.
- Configure your audience settings to complete the data sync process.
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
This is helpful with high CPU usage on small servers by making a call to the admin-ajax file and manually processing a single request at a time. 
### Multi-site Setups
The Mailchimp for WooCommerce supports Wordpress Multi Sites and below are a few things to note.
- Each site that has the plugin installed is a separate connection to Mailchimp.
- Deactivating - disables the plugin from sending data to Mailchimp. Upon reactivating the plugins original setup details will be intact. Deleting is necessary to connect a different Mailchimp Audience with WooCommerce.
- Deleting removes the connection between Mailchimp and WooCommerce, and uninstalls the plugin from your site.
Refer to the Wordpress Codex for more information about [Multisite Network Administration](https://codex.wordpress.org/Multisite_Network_Administration)

== Changelog ==
= 2.4.0 =
* update for latest Action Scheduler v3.1.4
* adds customer language on Cart and Order sync
* adds batch processing for queues
* support for Brazilian Portuguese pt_BR Language
= 2.3.6 =
* fix for Audience Defaults and Settings not visible
* improved campaign tracking on external payment gateways and API endpoints
* fix for transactionals being subscribed after force resync

[See changelog for all versions](https://raw.githubusercontent.com/mailchimp/mc-woocommerce/master/CHANGELOG.txt).