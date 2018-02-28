<?php
/*
Plugin Name: WP Background Processing
Plugin URI: https://github.com/A5hleyRich/wp-background-processing
Description: Asynchronous requests and background processing in WordPress.
Author: Delicious Brains Inc.
Version: 1.0
Author URI: https://deliciousbrains.com/
*/

$queue_folder_path = plugin_dir_path( __FILE__ );

require_once $queue_folder_path . 'queue/classes/wp-job.php';
require_once $queue_folder_path . 'queue/classes/wp-queue.php';
require_once $queue_folder_path . 'queue/classes/worker/wp-worker.php';
require_once $queue_folder_path . 'queue/classes/worker/wp-http-worker.php';

global $wp_queue;
$wp_queue = new WP_Queue();

// Add WP CLI commands
if (defined( 'WP_CLI' ) && WP_CLI) {
	try {
        /**
         * Service push to MailChimp
         *
         * <type>
         * : product_sync order_sync order product
         */
        function mailchimp_cli_push_command( $args, $assoc_args ) {
            if (is_array($args) && isset($args[0])) {
                switch($args[0]) {

                    case 'product_sync':
                        wp_queue(new MailChimp_WooCommerce_Process_Products());
                        WP_CLI::success("queued up the product sync!");
                        break;

                    case 'order_sync':
                        wp_queue(new MailChimp_WooCommerce_Process_Orders());
                        WP_CLI::success("queued up the order sync!");
                        break;

                    case 'order':
                        if (!isset($args[1])) {
                            wp_die('You must specify an order id as the 2nd parameter.');
                        }
                        wp_queue(new MailChimp_WooCommerce_Single_Order($args[1]));
                        WP_CLI::success("queued up the order {$args[1]}!");
                        break;

                    case 'product':
                        if (!isset($args[1])) {
                            wp_die('You must specify a product id as the 2nd parameter.');
                        }
                        wp_queue(new MailChimp_WooCommerce_Single_Product($args[1]));
                        WP_CLI::success("queued up the product {$args[1]}!");
                        break;
                }
            }
        };

        WP_CLI::add_command( 'mailchimp_push', 'mailchimp_cli_push_command');

        require_once $queue_folder_path . 'queue/classes/cli/queue-command.php';
        WP_CLI::add_command( 'queue', 'Queue_Command' );
    } catch (\Exception $e) {}
}

if (!mailchimp_running_in_console() && mailchimp_is_configured()) {
    // fire up the http worker container
    new WP_Http_Worker($wp_queue);
}

// if we're not running in the console, and the http_worker is not running
if (mailchimp_should_init_queue()) {
    try {
        // if we do not have a site transient for the queue listener
        if (!get_site_transient('http_worker_queue_listen')) {
            // set the site transient to expire in 50 seconds so this will not happen too many times
            // but still work for cron scripts on the minute mark.
            set_site_transient( 'http_worker_queue_listen', microtime(), 50);
            // if we have available jobs, call the http worker manually
            if ($wp_queue->available_jobs()) {
                mailchimp_call_http_worker_manually();
            }
        }
    } catch (\Exception $e) {}
}

if (!function_exists( 'wp_queue')) {
	/**
	 * WP queue.
	 *
	 * @param WP_Job $job
	 * @param int    $delay
	 */
	function wp_queue( WP_Job $job, $delay = 0 ) {
		global $wp_queue;
		$wp_queue->push( $job, $delay );
		do_action( 'wp_queue_job_pushed', $job );
	}
}
