<?php

class MailChimp_WooCommerce_Queue
{
    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $failed_table;

    /**
     * @var int
     */
    public $release_time = 60;

    public $max_tries = 3;

    protected static $_instance = null;

    /**
     * @return MailChimp_WooCommerce_Queue
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) return static::$_instance;
        return static::$_instance = new MailChimp_WooCommerce_Queue();
    }

    /**
     * WP_Queue constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table        = $wpdb->prefix . 'queue';
        $this->failed_table = $wpdb->prefix . 'failed_jobs';
    }

    /**
     * Push a job onto the queue.
     *
     * @param WP_Job $job
     * @param int    $delay
     *
     * @return $this
     */
    public function push(WP_Job $job, $delay = 0)
    {
        global $wpdb;

        $data = array(
            'job'          => maybe_serialize($job),
            'available_at' => $this->datetime($delay),
            'created_at'   => $this->datetime(),
        );

        if (!$wpdb->insert($this->table, $data) && $this->create_tables_if_required()) {
            if (!$wpdb->insert($this->table, $data)) {
                mailchimp_debug('Queue Job '.get_class($job), $wpdb->last_error);
            }
        }

        return $this;
    }

    /**
     * Release.
     *
     * @param object $job
     * @param int    $delay
     */
    public function release( $job, $delay = 0 )
    {
        if ($job->attempts >= $this->max_tries) {
            return $this->failed($job);
        }

        global $wpdb;

        $wpdb->update($this->table, array(
            'attempts'     => $job->attempts + 1,
            'locked'       => 0,
            'locked_at'    => null,
            'available_at' => $this->datetime( $delay ),
        ), array('id' => $job->id));
    }

    /**
     * Failed
     *
     * @param stdClass $job
     */
    protected function failed($job)
    {
        global $wpdb;

        $wpdb->insert($this->failed_table, array(
            'job'       => $job->job,
            'failed_at' => $this->datetime(),
        ));

        $payload = unserialize($job->job);

        if (method_exists($payload, 'failed')) {
            $payload->failed();
        }

        $this->delete($job);
    }

    /**
     * Delete.
     *
     * @param object $job
     */
    public function delete( $job )
    {
        global $wpdb;
        $wpdb->delete($this->table, array('id' => $job->id));
    }

    /**
     * Get MySQL datetime.
     *
     * @param int $offset Seconds, can pass negative int.
     *
     * @return string
     */
    protected function datetime($offset = 0)
    {
        return gmdate( 'Y-m-d H:i:s', time() + $offset);
    }

    /**
     * Available jobs.
     */
    public function available_jobs()
    {
        global $wpdb;
        $now = $this->datetime();
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE available_at <= %s", $now);
        return $wpdb->get_var($sql);
    }

    /**
     * Available jobs.
     */
    public function failed_jobs()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->failed_table}");
    }

    /**
     * Restart failed jobs.
     */
    public function restart_failed_jobs()
    {
        global $wpdb;
        $count = 0;
        $jobs  = $wpdb->get_results("SELECT * FROM {$this->failed_table}");

        foreach ($jobs as $job) {
            $this->push(maybe_unserialize($job->job));
            $wpdb->delete($this->failed_table, array('id' => $job->id));
            $count++;
        }

        return $count;
    }

    /**
     * Get next job.
     */
    public function get_next_job()
    {
        global $wpdb;
        $this->maybe_release_locked_jobs();
        $now = $this->datetime();
        $sql = $wpdb->prepare("SELECT * FROM {$this->table} WHERE locked = 0 AND available_at <= %s", $now);
        return $wpdb->get_row($sql);
    }

    /**
     * Maybe release locked jobs.
     */
    protected function maybe_release_locked_jobs()
    {
        global $wpdb;
        $expired = $this->datetime(-$this->release_time);
        $sql = $wpdb->prepare("UPDATE {$this->table} SET attempts = attempts + 1, locked = 0, locked_at = NULL WHERE locked = 1 AND locked_at <= %s", $expired);
        $wpdb->query($sql);
    }

    /**
     * Lock job.
     *
     * @param object $job
     */
    public function lock_job( $job )
    {
        global $wpdb;
        $wpdb->update( $this->table, array('locked' => 1, 'locked_at' => $this->datetime()), array('id' => $job->id));
    }

    /**
     * @return bool
     */
    public function create_tables_if_required()
    {
        global $wpdb;
        try {
            if (mailchimp_string_contains($wpdb->last_error, 'Table')) {
                mailchimp_debug('Queue Table Was Not Found!', 'Creating Tables');
                MailChimp_WooCommerce_Activator::create_queue_tables();
                return true;
            }
        } catch (\Exception $e) {
            mailchimp_error_trace($e, 'trying to create queue tables');
        }
        return false;
    }
}