<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.1
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/includes
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class MailChimp_WooCommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// create the queue tables because we need them for the sync jobs.
		static::create_queue_tables();

		// update the settings so we have them for use.
        $saved_options = get_option('mailchimp-woocommerce', false);

        // if we haven't saved options previously, we will need to create the site id and update base options
        if (empty($saved_options)) {
            mailchimp_clean_database();
            update_option('mailchimp-woocommerce', array());
            // only do this if the option has never been set before.
            if (!is_multisite()) {
                add_option('mailchimp_woocommerce_plugin_do_activation_redirect', true);
            }
        }

        // if we haven't saved the store id yet.
        $saved_store_id = get_option('mailchimp-woocommerce-store_id', false);
        if (empty($saved_store_id)) {
            // add a store id flag which will be a random hash
            update_option('mailchimp-woocommerce-store_id', uniqid(), 'yes');
        }

        if (class_exists('MailChimp_WooCommerce_MailChimpApi')) {
            // try this now for existing stores on an update.
            mailchimp_update_connected_site_script();
        }
	}

	/**
	 * Create the queue tables in the DB so we can use it for syncing.
	 */
	public static function create_queue_tables()
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$wpdb->hide_errors();

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}queue (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                attempts tinyint(1) NOT NULL DEFAULT 0,
                locked tinyint(1) NOT NULL DEFAULT 0,
                locked_at datetime DEFAULT NULL,
                available_at datetime NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}failed_jobs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                failed_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mailchimp_carts (
				id VARCHAR (255) NOT NULL,
				email VARCHAR (100) NOT NULL,
				user_id INT (11) DEFAULT NULL,
                cart text NOT NULL,
                created_at datetime NOT NULL,
				PRIMARY KEY  (email)
				) $charset_collate;";

		dbDelta( $sql );

		// set the Mailchimp woocommerce version at the time of install
		update_site_option('mailchimp_woocommerce_version', mailchimp_environment_variables()->version);
	}
}
