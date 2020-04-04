<?php

if ( ! class_exists( 'MailChimp_WooCommerce_Process_Full_Sync_Manager' ) ) {
	class MailChimp_WooCommerce_Process_Full_Sync_Manager {
		public function handle(){
			$item_count = get_option('mailchimp-woocommerce-sync.orders.items', 0); 
			error_log('checking status. orders left:'.$item_count);
			
			if ($item_count == 0) {
				mailchimp_flag_stop_sync();
			}
			else {
				$this->next();
			}
		}

		/**
		 *
		 */
		protected function next()
		{
			// this will paginate through all records for the resource type until they return no records.
			as_enqueue_async_action( 'MailChimp_WooCommerce_Process_Full_Sync_Manager', array(), 'mc-woocommerce' );

			//mailchimp_handle_or_queue(new static(), 10);
			mailchimp_log(get_called_class().'@handle', 'queuing up the next job');
		}
	}
}
