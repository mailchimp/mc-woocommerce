<?php

if ( ! class_exists( 'WP_Worker' ) ) {
	class WP_Worker {

		/**
		 * @var WP_Queue
		 */
		protected $queue;

		/**
		 * @var WP_Job
		 */
		protected $payload;

		/**
		 * WP_Worker constructor.
		 *
		 * @param WP_Queue $queue
		 */
		public function __construct( $queue ) {
			$this->queue = $queue;
		}

		/**
		 * Should run
		 *
		 * @return bool
		 */
		public function should_run() {

			if ($this->queue->available_jobs()) {
				return true;
			}

			return false;
		}

		/**
		 * Process next job.
		 *
		 * @return bool
		 */
		public function process_next_job() {
			$job = $this->queue->get_next_job();

			if (empty($job)) {
			    return false;
            }

			$this->payload = unserialize( $job->job );

			$this->queue->lock_job( $job );
			$this->payload->set_job( $job );

			try {
                $this->payload->handle();

                if ( $this->payload->is_deleted() ) {
                    // Job manually deleted, delete from queue
                    $this->queue->delete( $job );
                    return true;
                }

				if ( $this->payload->is_released() ) {
					// Job manually released, release back onto queue
					$this->queue->release( $job, $this->payload->get_delay() );
				}

				if ( ! $this->payload->is_deleted_or_released() ) {
					// Job completed, delete from queue
					$this->queue->delete( $job );
				}
			} catch ( Exception $e ) {
                mailchimp_log('queue.error', "{$e->getMessage()} on {$e->getLine()} in {$e->getFile()}", array('job' => get_class($this->payload)));
				$this->queue->release( $job );
				return false;
			}

			if (defined('WP_CLI') && WP_CLI && property_exists($this->payload, 'should_kill_queue_listener') && $this->payload->should_kill_queue_listener === true) {
			    wp_die('killing queue listener');
            }

			return true;
		}

		/**
		 * Get job name.
		 *
		 * @return object
		 */
		public function get_job_name() {
			return get_class( $this->payload );
		}

	}
}
