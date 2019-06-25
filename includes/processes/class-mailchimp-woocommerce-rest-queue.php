<?php

/**
 * Class MailChimp_WooCommerce_Rest_Queue
 */
class MailChimp_WooCommerce_Rest_Queue
{
    /**
     * @var WP_Queue
     */
    protected $queue;

    /**
     * @var WP_Job
     */
    protected $payload;

    /**
     * Has the worker been dispatched in this request?
     *
     * @var bool
     */
    protected $dispatched = false;

    /**
     * Timestamp of when this worker started processing the queue.
     *
     * @var int
     */
    protected $start_time;

    protected static $_instance = null;

    /**
     * @return MailChimp_WooCommerce_Rest_Queue
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) return static::$_instance;
        return static::$_instance = new MailChimp_WooCommerce_Rest_Queue();
    }

    /**
     * MailChimp_WooCommerce_Rest_Queue constructor.
     */
    public function __construct()
    {
        $this->queue = MailChimp_WooCommerce_Queue::instance();
    }

    /**
     * @return bool|int
     */
    public function handle()
    {
        // if we have the queue runner disabled, process 1 and exit.
        if (mailchimp_queue_is_disabled()) {
            return $this->process_next_job();
        }

        // Worker already running, die
        if ($this->is_worker_running()) {
            mailchimp_debug('rest_queue', 'blocked process because it was already running');
            return 'worker is running';
        }

        // Lock worker to prevent multiple instances spawning
        $this->lock_worker();

        // counter.
        $jobs_processed = 0;
        // Loop over jobs while within server limits
        while (!$this->time_exceeded() && !$this->memory_exceeded()) {
            if ($this->queue->available_jobs() > 0) {
                if ($this->process_next_job()) {
                    $jobs_processed++;
                }
                continue;
            }
            break;
        }

        // Unlock worker to allow another instance to be spawned
        $this->unlock_worker();

        // if the queue still has jobs, rinse and repeat.
        if ($this->queue->available_jobs() > 0) {
            $this->again();
        }

        return $jobs_processed;
    }

    /**
     * Process next job.
     *
     * @return bool
     */
    public function process_next_job()
    {
        $job = $this->queue->get_next_job();

        if (empty($job)) {
            return false;
        }

        $this->payload = unserialize($job->job);
        $this->queue->lock_job($job);
        $this->payload->set_job($job);

        try {
            $this->payload->handle();

            if ($this->payload->is_deleted()) {
                $this->queue->delete($job);
                return true;
            }

            if ($this->payload->is_released()) {
                $this->queue->release($job, $this->payload->get_delay());
            }

            if (!$this->payload->is_deleted_or_released()) {
                $this->queue->delete($job);
            }

        } catch ( Exception $e ) {
            mailchimp_log('queue.error', "{$e->getMessage()} on {$e->getLine()} in {$e->getFile()}", array('job' => get_class($this->payload)));
            $this->queue->release($job);
            return false;
        }

        if (defined('WP_CLI') && WP_CLI && property_exists($this->payload, 'should_kill_queue_listener') && $this->payload->should_kill_queue_listener === true) {
            wp_die('killing queue listener');
        }

        return true;
    }

    /**
     * Memory exceeded
     *
     * Ensures the worker process never exceeds 80%
     * of the maximum allowed PHP memory.
     *
     * @return bool
     */
    public function memory_exceeded()
    {
        $memory_limit = $this->get_memory_limit() * 0.8; // 80% of max memory
        return apply_filters( 'http_worker_memory_exceeded', memory_get_usage( true) >= $memory_limit);
    }

    /**
     * Get memory limit
     *
     * @return int
     */
    protected function get_memory_limit()
    {
        $memory_limit = function_exists( 'ini_get' ) ? ini_get( 'memory_limit' ) : '128M';
        if (!$memory_limit || -1 == $memory_limit) {
            // Unlimited, set to 32GB
            $memory_limit = '32000M';
        }

        return (int) preg_replace_callback('/(\-?\d+)(.?)/', function ($m) {
            if (!isset($m[2]) || $m[2] == '') {
                $m[1] = '128';
                $m[2] = 'M';
            }
            return $m[1] * pow(1024, strpos('BKMG', $m[2]));
        }, strtoupper($memory_limit));
    }

    /**
     * Time exceeded
     *
     * Ensures the worker never exceeds a sensible time limit (20s by default).
     * A timeout limit of 30s is common on shared hosting.
     *
     * @return bool
     */
    protected function time_exceeded()
    {
        $time_limit = apply_filters('http_worker_default_time_limit', 20);
        $finish = $this->start_time + $time_limit; // 20 seconds
        return apply_filters( 'http_worker_time_exceeded', ((time() + 2) >= $finish));
    }

    /**
     * Is HTTP worker disabled
     *
     * @return bool
     */
    public function is_http_worker_disabled()
    {
        return defined( 'DISABLE_WP_HTTP_WORKER' ) && DISABLE_WP_HTTP_WORKER === true;
    }

    /**
     * Is worker running
     *
     * Check if another instance of the HTTP worker is running.
     *
     * @return bool
     */
    public function is_worker_running()
    {
        return (bool) get_site_transient('http_worker_lock');
    }

    /**
     * Lock worker
     *
     * Lock the HTTP worker to prevent multiple instances running.
     */
    public function lock_worker()
    {
        $this->start_time = time(); // Set start time of current worker
        set_site_transient('http_worker_lock', microtime(), 60);
    }

    /**
     * Unlock worker
     *
     * Unlock the HTTP worker to allow other instances to be spawned.
     */
    public function unlock_worker()
    {
        if (!delete_site_transient('http_worker_lock')) {
            mailchimp_log('rest-queue', 'http_worker_lock did not delete properly - will respawn in 60 seconds.');
        }
    }

    /**
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_RateLimitError
     * @throws MailChimp_WooCommerce_ServerError
     */
    protected function again()
    {
        add_filter( 'https_local_ssl_verify', '__return_false', 1 );
        $url = esc_url_raw(rest_url('mailchimp-for-woocommerce/v1/queue/work'));
        $params = array(
            'timeout'   => 0.01,
            'blocking'  => false,
        );
        mailchimp_woocommerce_rest_api_get($url, $params, mailchimp_get_http_local_json_header());
    }
}