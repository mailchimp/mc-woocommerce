<?php

/**
 * Manage queue and jobs.
 *
 * @package wp-cli
 */
class Queue_Command extends WP_CLI_Command {

    /**
     * Timestamp of when this worker started processing the queue.
     *
     * @var int
     */
    protected $start_time;

    /**
     * Flush all of the records in the queue.
     */
    public function flush()
    {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}queue");
    }

    /**
     * Show all the records in the queue.
     */
    public function show()
    {
        global $wpdb;
        print_r($wpdb->get_results("SELECT * FROM {$wpdb->prefix}queue"));
    }

	/**
	 * Creates the queue tables.
	 *
	 * @subcommand create-tables
	 */
	public function create_tables( $args, $assoc_args = array() ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$wpdb->hide_errors();

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}queue (
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

		$sql = "CREATE TABLE {$wpdb->prefix}failed_jobs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                failed_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		WP_CLI::success( "Table {$wpdb->prefix}queue created." );
	}

    /**
     * Run the queue listener to process jobs
     *
     * ## OPTIONS
     *
     * [--force=<1>]
     * : Force the listener to ignore the transient and run
     *
     * [--daemon=<1>]
     * : Running the command as a true process using a manager to keep alive.
     *   If using WP CRON use --daemon=0
     *   If using a process manager, do nothing or pass in 1
     *
     * [--multiple=<1>]
     * : Allow multiple processes to run at the same time. ( default is 0 )
     *
     * [--sleep_processing=<1>]
     * : How long to sleep between jobs. ( default is 1 second )
     *
     * [--sleep_empty=<1>]
     * : How long to sleep between jobs when nothing is in the queue. ( default is 5 seconds )
     * ---
     *
     * ## EXAMPLES
     *
     *     wp queue listen --daemon=1
     *     wp queue listen --daemon=0 --sleep_empty=10
     *
     * ---
     *
     * @subcommand listen
     * @param $args
     * @param array $assoc_args
     */
	public function listen( $args, $assoc_args = array() ) {
		global $wp_queue;

        $process_id = getmypid();
        $this->start_time = time(); // Set start time of current command

        $allow_multiple = (isset($assoc_args['multiple']) ? (bool) $assoc_args['multiple'] : null) === true;
        $running_as_daemon = (isset($assoc_args['daemon']) ? (bool) $assoc_args['daemon'] : null) === true;
        $force = (isset($assoc_args['force']) ? (bool) $assoc_args['force'] : null) === true;

		$sleep_between_jobs = isset($assoc_args['sleep_processing']) ? (int) $assoc_args['sleep_processing'] : 1;
        $sleep_when_empty = isset($assoc_args['sleep_empty']) ? (int) $assoc_args['sleep_empty'] : 5;

        $transient = 'mailchimp_woocommerce_queue_listen';

        if (!$force && (!$allow_multiple && get_site_transient($transient))) {
            WP_CLI::log('Currently running in another process');
            return;
        }

        mailchimp_debug("queue", $message = '[start] queue listen process_id = '.$process_id);

        WP_CLI::log($message);

		$worker = new WP_Worker( $wp_queue );

		$loop_counter = 0;

		// if the user specifies that they want to run as a daemon we need to allow that.
		while ($running_as_daemon || (!$this->time_exceeded() && !$this->memory_exceeded())) {

            $loop_counter++;

		    // allow queue to break out of the forever loop if something is going wrong by adding a transient
		    if ((bool) get_site_transient('kill_wp_queue_listener')) {
		        break;
            }

            // if we're doing single processing only, set the transient
            if (!$allow_multiple) {
                set_site_transient($transient, microtime(), 50);
            }

            // log it in increments of 20 to be lighter on the log file
            if ($loop_counter % 20 === 0) {
                mailchimp_debug("queue listen", "process id {$process_id} :: loop #{$loop_counter}");
            }

            $sleep = $sleep_when_empty;

		    // if the worker has a job, apply the sleep between job timeout
			if ($worker->should_run() && $worker->process_next_job()) {
                $sleep = $sleep_between_jobs;
                if (($job_name = $worker->get_job_name()) !== 'WP_Worker') {
                    WP_CLI::success('Processed: ' . $job_name);
                }
			}

            sleep($sleep);
		}

		if (!$allow_multiple) {
            delete_site_transient($transient);
        }

        mailchimp_debug("queue", $message = '[end] queue listen process_id = '.$process_id);
        WP_CLI::log($message);
	}

	/**
	 * Process the next job in the queue.
     * @subcommand work
	 */
	public function work( $args, $assoc_args = array() ) {
		global $wp_queue;

		$worker = new WP_Worker( $wp_queue );

		if ( $worker->should_run() ) {
			if ( $worker->process_next_job() ) {
				WP_CLI::success( 'Processed: ' . $worker->get_job_name() );
			} else {
				WP_CLI::warning( 'Failed: ' . $worker->get_job_name() );
			}
		} else {
			WP_CLI::log( 'No jobs to process...' );
		}
	}

	/**
	 * Show queue status.
	 */
	public function status( $args, $assoc_args = array() ) {
		global $wp_queue;
		WP_CLI::log( $wp_queue->available_jobs() . ' jobs in the queue' );
		WP_CLI::log( $wp_queue->failed_jobs() . ' failed jobs' );
	}

	/**
	 * Push failed jobs back onto the queue.
	 *
	 * @subcommand restart-failed
	 */
	public function restart_failed( $args, $assoc_args = array() ) {
		global $wp_queue;
		if ( ! $wp_queue->failed_jobs() ) {
			WP_CLI::log( 'No failed jobs to restart...' );
			return;
		}
		$count = $wp_queue->restart_failed_jobs();
		WP_CLI::success( $count . ' failed jobs pushed to the queue' );
	}

    /**
     * Memory exceeded
     *
     * Ensures the worker process never exceeds 80%
     * of the maximum allowed PHP memory.
     *
     * @return bool
     */
    protected function memory_exceeded() {
        return memory_get_usage( true ) >= ($this->get_memory_limit() * 0.8);
    }

    /**
     * Get memory limit
     *
     * @return int
     */
    protected function get_memory_limit() {
        return intval($this->getServerMemoryLimit()) * 1024 * 1024;
    }

    /**
     * Time exceeded
     *
     * Ensures the worker never exceeds a sensible time limit (50s by default).
     * A timeout limit of 30s is common on shared hosting.
     *
     * @return bool
     */
    protected function time_exceeded() {
        return time() >= $this->start_time + 50;

        $time_limit = $this->getServerMaxExecutionTime();
        if (!$time_limit || -1 == $time_limit) {
            $time_limit = 1000000;
        }
        return time() >= $this->start_time + apply_filters( 'cli_worker_default_time_limit', ($time_limit - 10));
    }

    /**
     * @return int
     */
    protected function getServerMaxExecutionTime()
    {
        return (int) function_exists( 'ini_get' ) ? ini_get( 'max_execution_time' ) : 30;
    }

    /**
     * @return string
     */
    protected function getServerMemoryLimit()
    {
        $memory_limit = function_exists( 'ini_get' ) ? ini_get( 'memory_limit' ) : '128M';
        if (!$memory_limit || -1 == $memory_limit) {
            $memory_limit = '32000M';
        }
        return $memory_limit;
    }

}

