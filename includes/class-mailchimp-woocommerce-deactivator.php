<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.1
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/includes
 * @author     Ryan Hungate <ryan@mailchimp.com>
 */
class MailChimp_Woocommerce_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option('mailchimp-woocommerce');
		delete_option('mailchimp-woocommerce-errors.store_info');
		delete_option('mailchimp-woocommerce-sync.orders.completed_at');
		delete_option('mailchimp-woocommerce-sync.orders.current_page');
		delete_option('mailchimp-woocommerce-sync.products.completed_at');
		delete_option('mailchimp-woocommerce-sync.products.current_page');
		delete_option('mailchimp-woocommerce-sync.syncing');
		delete_option('mailchimp-woocommerce-validation.api.ping');
		delete_option('mailchimp-woocommerce-cached-api-lists');
		delete_option('mailchimp-woocommerce-cached-api-ping-check');
	}

}
