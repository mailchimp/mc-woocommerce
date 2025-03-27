## Mailchimp for WooCommerce Integration Guide

This guide provides instructions to seamlessly integrate your WooCommerce store with Mailchimp, outlining installation, configuration, synchronization, and support information.

## Before You Begin

Before installing the plugin, ensure:

- **WooCommerce** is installed and activated on your WordPress site.
- Your environment meets [WooCommerce's system requirements](https://docs.woocommerce.com/document/server-requirements/).
- The **WordPress REST API** is enabled.
- You have access to a staging environment for testing.

## Installation and Activation

1. **Download and Install**:
   - Visit [Mailchimp for WooCommerce](https://wordpress.org/plugins/mailchimp-for-woocommerce/).
   - Click **Download**, then upload the ZIP file via `Plugins > Add New > Upload Plugin`.

2. **Activate**:
   - Click **Activate Plugin** upon installation.

After activation, you’ll be directed to the initial setup.

## Initial Setup and Synchronization

1. **Connect to Mailchimp**:
   - Click **Connect Account**.
   - Log in and authorize Mailchimp to sync data with your WooCommerce store.

2. **Initial Sync Options**:

**Import customers (initial sync)**  
Choose how you'll add your WooCommerce customers to Mailchimp:

- **Sync as subscribed**  
  Indicates you've received permission to market to your customers. [Learn about permission](https://mailchimp.com/help/about-permission/).

- **Sync as non-subscribed**  
  Indicates you haven't received permission to market. You can still send transactional emails, postcards, and target them with ads.

- **Sync existing contacts only**  
  Sync only WooCommerce customers already in your Mailchimp audience.

*Note*: If syncing as subscribed or non-subscribed, ensure your Mailchimp plan covers the total number of contacts to avoid additional charges. [Learn more about charges](https://mailchimp.com/help/about-additional-contact-charges/).

**Import customers (ongoing sync)**  
- **Sync new non-subscribed contacts**  
  Import new customers who haven't opted in. (Required for Abandoned Cart automations.)

- **Tag WooCommerce customers**  
  Apply tags to contacts imported via the plugin for easier segmentation and personalization.

3. Click **Start Sync** to begin synchronizing your WooCommerce data with Mailchimp.

## Additional Features

Enhance marketing efforts with:

- **Pop-Up Forms**: Capture site visitors as subscribers.
- **Promo Codes**: Integrate WooCommerce discount codes into Mailchimp emails.
- **Landing Pages**: Promote products or sales to build your audience.
- **WPML Compatibility**: Multilingual support via [WPML](https://wpml.org/plugin/mailchimp-for-woocommerce/).

## Deactivation and Deletion

1. **Deactivate**:
   - Navigate to `Plugins` > `Installed Plugins`.
   - Click **Deactivate** on Mailchimp for WooCommerce.

2. **Delete (Optional)**:
   - After deactivation, click **Delete**.

Deleting removes synced e-commerce data from WooCommerce but retains subscriber details in Mailchimp.

## Troubleshooting and Support

To facilitate troubleshooting, enable remote support:

- Navigate to the `Support` tab.
- Check the box **Enable Remote Support**.
- Click **Save changes**.

For additional assistance:

- **Logs and Debugging**: Enable debugging logs in plugin settings.
- **Documentation**: Visit the [GitHub Wiki](https://github.com/mailchimp/mc-woocommerce/wiki) and [FAQs](https://github.com/mailchimp/mc-woocommerce/blob/master/README.txt).
- **Support**: Submit issues via [GitHub](https://github.com/mailchimp/mc-woocommerce/issues) or contact Mailchimp support.