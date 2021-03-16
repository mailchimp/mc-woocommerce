=== Mailchimp for WooCommerce ===
Contributors: ryanhungate, Mailchimp
Tags: ecommerce,email,workflows,mailchimp
Donate link: https://mailchimp.com
Requires at least: 4.9
Tested up to: 5.7
Stable tag: 2.5.1
Requires PHP: 7.0
WC requires at least: 3.5
WC tested up to: 5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Connect your store to your Mailchimp audience to track sales, create targeted emails, send abandoned cart emails, and more.

== Description ==
Join the 17 million customers who use Mailchimp, the world's largest marketing automation platform, to develop their e-commerce marketing strategy. With the official Mailchimp for WooCommerce integration, your customers and their purchase data are automatically synced with your Mailchimp account, making it easy to send targeted campaigns, automatically follow up with customers post-purchase, recommend products, recover abandoned carts, and measure the ROI of your marketing efforts. And it's completely free.
###What you can do with this plugin
- Sync to your Audience in Mailchimp with purchase data.
- Sync new subscribers to your Audience when they create an account and opt-in.
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

== Installation ==
###Before You Start
Here are some things to know before you begin this process.
- This plugin requires you to have the [WooCommerce plugin](https://woocommerce.com/) already installed and activated in WordPress.
- Your hosting environment must meet [WooCommerce's minimum requirements](https://docs.woocommerce.com/document/server-requirements), including PHP 7.0 or greater.
- `WP_CRON` must be activated with your hosting provider to sync data. Please verify that it is enabled.
- We recommend you use this plugin in a staging environment before installing it on production servers. To learn more about staging environments, [check out these related Wordpress plugins](https://wordpress.org/plugins/search.php?q=staging).
- Mailchimp for WooCommerce syncs the customer’s first name, last name, email address, and orders.
- WooCommerce customers who haven't signed up for marketing emails will appear in the **Transactional** portion of your audience, and cannot be exported.

###Getting Started
You’ll need to do a few things to connect your WooCommerce store to Mailchimp.
- Download the plugin.
- Install the plugin on your WordPress Admin site.
- Connect securely to your Mailchimp account via secure OAuth pop-up window.
- Configure your Audience settings to complete the data sync process.
- If you have more advanced configuration needs, please refer to our [GitHub wiki](https://github.com/mailchimp/mc-woocommerce/wiki)

== Frequently Asked Questions ==

= Who is subscribed to my Audience with this plugin? =

- Customers are subscribed to your Audience when they opt-in at checkout or when an account is created. If an account is created through the standard WooCommerce `My Account` page, they must opt-in to be added.
- Customers are sent to your Audience as `Transactional` if they do not opt-in. This is done so you can send [abandoned carts](https://mailchimp.com/help/create-an-abandoned-cart-email/) or [order notifications](https://mailchimp.com/help/create-order-notifications/).
- If double opt-in is enabled, customers will only be subscribed to your Audience if they approve the subscription from the confirmation email that is sent.

= What is the recommended way to sync larger stores? =

To optimize the performance of your Mailchimp integration we recommend running the queue in CLI mode. Please refer to [this guide](https://github.com/mailchimp/mc-woocommerce/wiki/Advanced-Queue-Setup-In-CLI-mode) in our Wiki.

= Are multisite configurations supported?

Multisites are supported, with a few caveats. Please refer to our [Wiki page](https://github.com/mailchimp/mc-woocommerce/wiki/Multisite-Setups) on this topic for more information.

= Why aren't product categories being sent to Mailchimp? =
At this time, the synchronization of product categories from WooCommerce to Mailchimp is not supported by the Mailchimp API.

= My sync is slow, or has stalled =
- If you're using the current version of the plugin, it utilizes a queue system powered by [Action Scheduler](https://actionscheduler.org/). It depends on `WP_CRON` to be activated with your hosting provider. Please verify that it is enabled.
- If you're using a host that makes use of CPU throttling, please check to see if you've hit your limit after initiating the sync.
- If you're using Redis, Nginx or MemCache, check to see if you or your hosting provider can exclude certain paths to the `REST API` and `/wp-json/mailchimp-for-woocommerce`.
- If you have a large number of plugins being used, you may need to bump up your memory limit on your server (1GB for example) to accommodate the initial sync.

= My question is not listed =
If you are unable to sync or connect with Mailchimp, you can open a ticket on our [Github plugin page](https://github.com/mailchimp/mc-woocommerce/issues). Please provide the version of the plugin and PHP you're using, any fatal errors in the WooCommerce logs (WooCommerce -> Status -> Logs) you're seeing, along with relevant information to the problem you're experiencing.

== Changelog ==
= 2.5 =
* interface reskin
* fix for fatal error on disabled WoooCommerce admin

[Historical Changelog](https://raw.githubusercontent.com/mailchimp/mc-woocommerce/master/CHANGELOG.txt)

