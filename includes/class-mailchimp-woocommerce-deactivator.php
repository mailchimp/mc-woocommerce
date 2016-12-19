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
		// if the api is valid, we need to try to delete the store
		if (($api = mailchimp_get_api())) {
			$api->deleteStore(mailchimp_get_store_id());
		}

		delete_option('mailchimp-woocommerce-sync.started_at');
		delete_option('mailchimp-woocommerce-sync.completed_at');
	}

}
