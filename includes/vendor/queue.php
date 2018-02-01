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
        require_once $queue_folder_path . 'queue/classes/cli/queue-command.php';
        WP_CLI::add_command( 'queue', 'Queue_Command' );
    } catch (\Exception $e) {}
}

// if we're not running in the console, and the http_worker is not running
if (!mailchimp_running_in_console() && !mailchimp_http_worker_is_running()) {
    try {
        // fire up the http worker container
        new WP_Http_Worker($wp_queue);
        // if we do not have a site transient for the queue listener and we have available jobs
        if (!get_site_transient('http_worker_queue_listen') && $wp_queue->available_jobs()) {
            // set the site transient to expire in 60 seconds so this will not happen too many times.
            set_site_transient( 'http_worker_queue_listen', microtime(), 60);
            // call the http worker manually
            mailchimp_call_http_worker_manually();
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
