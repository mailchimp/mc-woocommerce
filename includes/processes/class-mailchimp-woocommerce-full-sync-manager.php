<?php
/**
 * Created by Vextras.
 *
 * Name: Pedro Germani
 * Email: pedro.germani@gmail.com
 * Date: 04/07/2020
 */

if ( ! class_exists( 'MailChimp_WooCommerce_Process_Full_Sync_Manager' ) ) {
	class MailChimp_WooCommerce_Process_Full_Sync_Manager {
		/**
		 * @var string
		 */
		private $plugin_name = 'mailchimp-woocommerce';

		/**
		 * @throws MailChimp_WooCommerce_Error
		 * @throws MailChimp_WooCommerce_RateLimitError
		 * @throws MailChimp_WooCommerce_ServerError
		 */
		public function start_sync() {
			
			$this->flag_start_sync();
			
			$customers_sync = new MailChimp_WooCommerce_Process_Customers();
		
			// start sync processes creation
			$customers_sync->createSyncManagers();

		}

		/**
		 * @return $this
		 */
		public function flag_start_sync() {
			$job = new MailChimp_Service();

			$job->removeSyncPointers();

			\Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.config.resync", false);
			\Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.customers.current_page", 1);
            \Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.products.current_page", 1);
            \Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.coupons.current_page", 1);
            \Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.orders.current_page", 1);

            \Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.syncing", true);
			\Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.started_at", time());

			// let this happen if they start the sync again.
			mailchimp_delete_transient('stop_sync');

			if (! \Mailchimp_Woocommerce_DB_Helpers::get_option("{$this->plugin_name}-sync.completed_at")) {
				\Mailchimp_Woocommerce_DB_Helpers::update_option("{$this->plugin_name}-sync.initial_sync", 1);
			} else \Mailchimp_Woocommerce_DB_Helpers::delete_option("{$this->plugin_name}-sync.initial_sync");

			global $wpdb;
			try {
				$wpdb->show_errors(false);
				mailchimp_delete_as_jobs();
				mailchimp_flush_sync_job_tables();
				$wpdb->show_errors();
			} catch (Exception $e) {}

			mailchimp_log("{$this->plugin_name}-sync.started", "Starting Sync :: ".date('D, M j, Y g:i A'));

			// flag the store as syncing
			mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), true);

			return $this;
		}

		/**
		 * @throws MailChimp_WooCommerce_Error
		 * @throws MailChimp_WooCommerce_RateLimitError
		 * @throws MailChimp_WooCommerce_ServerError
		 */
		function flag_stop_sync()
		{
			// this is the last thing we're doing so it's complete as of now.
			mailchimp_set_data('sync.syncing', false);
			mailchimp_set_data('sync.completed_at', time());

			// set the current sync pages back to 1 if the user hits resync.
			mailchimp_set_data('sync.customers.current_page', 1);
			mailchimp_set_data('sync.products.current_page', 1);
			mailchimp_set_data('sync.coupons.current_page', 1);
			mailchimp_set_data('sync.orders.current_page', 1);

			$sync_started_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.started_at');
			$sync_completed_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.completed_at');

			$sync_total_time = $sync_completed_at - $sync_started_at;
			$time = gmdate("H:i:s",$sync_total_time);

			mailchimp_log('sync.completed', "Finished Sync :: ".date('D, M j, Y g:i A'). " (total time: ".$time.")");

			// flag the store as sync_finished
			mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), false);

			// send the sync finished email.
			MailChimp_WooCommerce_Admin::instance()->mailchimp_send_sync_finished_email();
			
			mailchimp_update_communication_status();

		}

		/**
		 * @throws MailChimp_WooCommerce_Error
		 * @throws MailChimp_WooCommerce_RateLimitError
		 * @throws MailChimp_WooCommerce_ServerError
		 */
		public function handle(){
			// if we have a transient telling us to stop this sync, just break out here instead of
			// respawn and try to delete.
			if (mailchimp_get_transient('stop_sync', false)) {
				return;
			}
			
			// get started queueing processes
			$started = array(
                'customers' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.customers.started_at'),
				'coupons' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.coupons.started_at'),
				'products' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.products.started_at'),
				'orders' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.orders.started_at')
			);

			// get completed queueing processes
			$completed = array(
                'customers' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.customers.completed_at'),
				'coupons' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.coupons.completed_at'),
				'products' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.products.completed_at'),
				'orders' => \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.orders.completed_at')
			);

            // make sure customers are fully synced before syncing products
            if ($completed['customers'] && !$started['products']) {
                mailchimp_log('sync.full_sync_manager.queue', 'Starting PRODUCTS queueing.');
                // create product sync
                $product_sync = new MailChimp_WooCommerce_Process_Products();
                // trigger subsequent jobs creation
                $product_sync->createSyncManagers();
            }

            // allow products and coupons to be synced simultaneously
            if ($completed['products'] && !$started['coupons']) {
                mailchimp_log('sync.full_sync_manager.queue', 'Starting CUSTOMERS queueing.');
                // create Product Sync object
                $coupons_sync = new MailChimp_WooCommerce_Process_Coupons();
                // trigger subsequent jobs creation
                $coupons_sync->createSyncManagers();
            }

			// Only start orders when product jobs are all finished
			if ($completed['coupons'] && $completed['products'] && !$started['orders'] ) {
				// check if we have products still to be synced
				if (mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Product') == 0 && mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Process_Products') <= 0) {
					
					$prevent_order_sync = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.orders.prevent', false);

					// only do this if we're not strictly syncing products ( which is the default ).
					if (!$prevent_order_sync) {
						// since the products are all good, let's sync up the orders now.
						$order_sync = new MailChimp_WooCommerce_Process_Orders();
						// trigger subsequent jobs creation
						$order_sync->createSyncManagers();
					}

					// since we skipped the orders feed we can delete this option.
					\Mailchimp_Woocommerce_DB_Helpers::delete_option('mailchimp-woocommerce-sync.orders.prevent');
				}
			}

			if ($completed['orders']) {
				if (mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Order') <= 0 && mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Process_Orders') <= 0) {
					mailchimp_set_transient('stop_sync', 600);
					$this->flag_stop_sync();
					mailchimp_log('sync', "Sync manager has finished queuing jobs and flagged the store as not syncing.");
                    try {
                        as_unschedule_action('MailChimp_WooCommerce_Process_Full_Sync_Manager', array(), 'mc-woocommerce' );
                    } catch (Exception $e) {
                    	mailchimp_error('sync.unschedule.error', $e->getMessage());
                    }
					return true;
				}
			}

			// Trigger respawn
			$this->recreate();
		}

		/**
		 *
		 */
		protected function recreate()
		{
			as_schedule_single_action(strtotime( '+10 seconds' ), 'MailChimp_WooCommerce_Process_Full_Sync_Manager', array(), 'mc-woocommerce' );	
		}
	}
}
