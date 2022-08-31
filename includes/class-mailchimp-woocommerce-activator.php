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
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 * @since    1.0.0
	 */
	public static function activate() {

		// Create the queue tables
		static::create_queue_tables();

		// we shouldn't have to do this anymore.
		//static::migrate_jobs();

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
		
		// set initial comm status
		mailchimp_update_communication_status();
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

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mailchimp_carts (
				id VARCHAR (255) NOT NULL,
				email VARCHAR (100) NOT NULL,
				user_id INT (11) DEFAULT NULL,
                cart text NOT NULL,
                created_at datetime NOT NULL,
				PRIMARY KEY  (email)
				) $charset_collate;";

		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mailchimp_jobs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			obj_id text,
			job text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";

		dbDelta( $sql );

		// set the Mailchimp woocommerce version at the time of install
		update_site_option('mailchimp_woocommerce_version', mailchimp_environment_variables()->version);
	}

	/**
	 *
	 */
	public static function migrate_jobs() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
        if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}queue';") == $wpdb->prefix.'queue') {
			mailchimp_log('update.db','Migrating job to Action Scheduler');
			$sql = "SELECT * FROM {$wpdb->prefix}queue;";
			$queue_jobs = $wpdb->get_results($sql);
			foreach ($queue_jobs as $queue_job) {
				$job = unserialize($queue_job->job);
				$job->job = $job;
				$job->id = static::get_possible_job_ids($job);	
				mailchimp_as_push($job, 90);
			}
		}
	}

	/**
	 * @param $job
	 *
	 * @return null
	 */
	private static function get_possible_job_ids($job) {
		$id = null;
		
		if (isset($job->id)) $id = $job->id;
		if (isset($job->product_id)) $id = $job->product_id;
		if (isset($job->order_id)) $id = $job->order_id;
		if (isset($job->unique_id)) $id = $job->unique_id;
		if (isset($job->user_id)) $id = $job->user_id;
		if (isset($job->post_id)) $id = $job->post_id;
			
		return $id;
	}

}
