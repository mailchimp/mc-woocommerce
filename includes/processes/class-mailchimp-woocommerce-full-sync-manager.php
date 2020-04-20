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
		 * Start the full sync process
		 */
		public function start_sync() {
			
			$this->flag_start_sync();
			
			$coupons_sync = new MailChimp_WooCommerce_Process_Coupons();
		
			// start sync processes creation
			$coupons_sync->createSyncManagers();

		}

		/**
		 * @return $this
		 */
		public function flag_start_sync() {
			$job = new MailChimp_Service();

			$job->removeSyncPointers();

			update_option("{$this->plugin_name}-sync.config.resync", false);
			update_option("{$this->plugin_name}-sync.orders.current_page", 1);
			update_option("{$this->plugin_name}-sync.products.current_page", 1);
			update_option("{$this->plugin_name}-sync.coupons.current_page", 1);

			update_option("{$this->plugin_name}-sync.syncing", true);
			update_option("{$this->plugin_name}-sync.started_at", time());

			if (! get_option("{$this->plugin_name}-sync.completed_at")) {
				update_option("{$this->plugin_name}-sync.initial_sync", 1);
			} else delete_option("{$this->plugin_name}-sync.initial_sync");

			global $wpdb;
			try {
				$wpdb->show_errors(false);
				mailchimp_delete_as_jobs();
				mailchimp_flush_sync_job_tables();
				$wpdb->show_errors(true);
			} catch (\Exception $e) {}

			mailchimp_log("{$this->plugin_name}-sync.started", "Starting Sync :: ".date('D, M j, Y g:i A'));

			// flag the store as syncing
			mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), true);

			return $this;
		}

		/**
		 * 
		 */
		function flag_stop_sync()
		{
			// this is the last thing we're doing so it's complete as of now.
			mailchimp_set_data('sync.syncing', false);
			mailchimp_set_data('sync.completed_at', time());

			// set the current sync pages back to 1 if the user hits resync.
			mailchimp_set_data('sync.orders.current_page', 1);
			mailchimp_set_data('sync.products.current_page', 1);
			mailchimp_set_data('sync.coupons.current_page', 1);

			$sync_started_at = get_option('mailchimp-woocommerce-sync.started_at');
			$sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');

			$sync_total_time = $sync_completed_at - $sync_started_at;
			$time = gmdate("H:i:s",$sync_total_time);

			mailchimp_log('sync.completed', "Finished Sync :: ".date('D, M j, Y g:i A'). " (total time: ".$time.")");

			// flag the store as sync_finished
			mailchimp_get_api()->flagStoreSync(mailchimp_get_store_id(), false);
			
			mailchimp_update_communication_status();

		}

		public function handle(){
			// Trigger respawn
			$this->recreate();
			
			// get started queueing processes
			$started = array(
				'coupons' => get_option('mailchimp-woocommerce-sync.coupons.started_at'),
				'products' => get_option('mailchimp-woocommerce-sync.products.started_at'),
				'orders' => get_option('mailchimp-woocommerce-sync.orders.started_at')
			);

			// get completed queueing processes
			$completed = array(
				'coupons' => get_option('mailchimp-woocommerce-sync.coupons.completed_at'),
				'products' => get_option('mailchimp-woocommerce-sync.products.completed_at'),
				'orders' => get_option('mailchimp-woocommerce-sync.orders.completed_at')
			);

			// allow products and coupons to be synced simultaneously
			if ($started['coupons'] && !$started['products']) {
				mailchimp_log('sync.full_sync_manager.queue', 'Starting PRODUCTS queueing.');
				//create Product Sync object
				$product_sync = new MailChimp_WooCommerce_Process_Products();
	
				// queue first job
				//mailchimp_handle_or_queue($product_sync);
				
				//trigger subsequent jobs creation
				$product_sync->createSyncManagers();			
			}

			// Only start orders when product jobs are all finished
			if ($completed['products'] && !$started['orders'] ) {
				// check if we have products still to be synced
				if (mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Product') == 0 && mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Process_Products') <= 0) {
					
					$prevent_order_sync = get_option('mailchimp-woocommerce-sync.orders.prevent', false);

					// only do this if we're not strictly syncing products ( which is the default ).
					if (!$prevent_order_sync) {
						// since the products are all good, let's sync up the orders now.
						$order_sync = new MailChimp_WooCommerce_Process_Orders();
						// // queue first job
						//mailchimp_handle_or_queue($order_sync);
						// //trigger subsequent jobs creation
						$order_sync->createSyncManagers();
					}

					// since we skipped the orders feed we can delete this option.
					delete_option('mailchimp-woocommerce-sync.orders.prevent');	
				}
				
			}

			if ($completed['orders']) {
				if (mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Single_Order') <= 0 && mailchimp_get_remaining_jobs_count('MailChimp_WooCommerce_Process_Orders') <= 0) {
					$this->flag_stop_sync();
					as_unschedule_action('MailChimp_WooCommerce_Process_Full_Sync_Manager', array(), 'mc-woocommerce' );		
				}	
			}
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
