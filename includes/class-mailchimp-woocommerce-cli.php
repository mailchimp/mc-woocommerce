<?php

/**
 * Manage Mailchimp for Woocommerce syncronization jobs.
 *
 * @package wp-cli
 */
class Mailchimp_Wocoomerce_CLI extends WP_CLI_Command {

    /**
     * Timestamp of when this worker started processing the queue.
     *
     * @var int
     */
    protected $start_time;
    protected $pid;
    protected $command_called;

    /**
     * Mailchimp_Wocoomerce_CLI constructor.
     */
    public function __construct()
    {
        $this->pid = getmypid();
    }

    /**
     * Flush all of the records in the queue.
     */
    public function flush($args, $assoc_args = array())
    {
        global $wpdb;
        WP_CLI::confirm( "This will delete all current queued sync jobs, and entries from {$wpdb->prefix}mailchimp_jobs table. Are you sure?", $assoc_args );
        mailchimp_delete_as_jobs();
        $wpdb->query("DELETE FROM {$wpdb->prefix}mailchimp_jobs");
    }

    /**
     * Show all the records in the queue.
     */
    public function show()
    {
        global $wpdb;
        WP_CLI::log("Showing contents of {$wpdb->prefix}mailchimp_jobs"); 
        print_r($wpdb->get_results("SELECT * FROM {$wpdb->prefix}mailchimp_jobs"));
        exit;
    }

	/**
	 * Creates the queue tables.
	 *
	 * @subcommand create-tables
	 */
	public function create_tables( $args, $assoc_args = array() ) {
        install_mailchimp_queue();
		WP_CLI::success( "Table {$wpdb->prefix}queue created." );
	}

    /**
     * Run the queue listener to process jobs
     *
     * ## OPTIONS
     *
     * [--force=<0>]
     * : Whether to force execution despite the maximum number of concurrent processes being exceeded.
     *
     * ---
     *
     * ## EXAMPLES
     *
     *     wp queue listen --force=1
     *
     * ---
     *
     * @subcommand listen
     * @param $args
     * @param array $assoc_args
     */
	public function listen( $args, $assoc_args = array() ) {
        mailchimp_debug('cli.queue.listen.process','Starting command `action-scheduler run`'); 
        WP_CLI::warning(WP_CLI::colorize('%Wqueue listen%n').' command is deprecated since Mailchimp for Woocommerce version 2.3. Please use '.WP_CLI::colorize('%ywp action-scheduler run --group="mc-woocommerce%n"').' instead'); 
        WP_CLI::log('Starting sync'); 
        
        $force_arg = '';
        
        $force = (isset($assoc_args['force']) ? (bool) $assoc_args['force'] : null) === true;
        if ($force) {
            $force_arg = " --force=1 ";
        }

        $options = array(
            'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
            //'parse'      => 'json', // Parse captured STDOUT to JSON array.
            'launch'     => true,  // Reuse the current process.
            'exit_error' => true,   // Halt script execution on error.
          );

        $command = 'action-scheduler run --group=mc-woocommerce'.$force_arg;
        $output = WP_CLI::runcommand( $command, $options );
        WP_CLI::log($output);  
        exit;
    }
    
    /**
	 * Deprecated commands
	 */
	public function work( $args, $assoc_args = array() ) {
        WP_CLI::warning(WP_CLI::colorize('%Wqueue work%n').' command is deprecated since Mailchimp for Woocommerce version 2.3. Please use '.WP_CLI::colorize('%ywp action-scheduler run --group="mc-woocommerce%n"').' instead'); 
        exit;
    }
    
	public function status( $args, $assoc_args = array() ) {
        WP_CLI::warning(WP_CLI::colorize('%Wqueue status%n').' command is deprecated since Mailchimp for Woocommerce version 2.3.'); 
        exit;
    }
    
	public function restart_failed( $args, $assoc_args = array() ) {
		WP_CLI::warning(WP_CLI::colorize('%Wqueue restart_failed%n').' command is deprecated since Mailchimp for Woocommerce version 2.3.'); 
        exit;
    }
}

