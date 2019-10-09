<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_Job' ) ) {
	abstract class Mailchimp_Woocommerce_Job {

	    public $should_kill_queue_listener = false;
		private $attempts = 0;

		/**
		 * @var stdClass
		 */
		private $job;

		/**
		 * Set job
		 *
		 * @param $job
		 */
		public function set_job( $job ) {
			$this->job = $job;
		}

		/**
		 * Set attempts
		*/
		public function set_attempts( $attempts ) {
			$this->attempts = $attempts;
		}

		/**
		 * Get attempts
		*/
		public function get_attempts( ) {
			return $this->attempts;
		}

		/**
		 * Reschedule action 4 times
		*/
		public function retry( $delay = 30 ) {
			$job = $this;
			if (null == $job->attempts) $job->set_attempts(0);
			$job->set_attempts($job->get_attempts() + 1);
			mailchimp_as_push($job, $delay);
		}

		/**
		 * Handle the job.
		 */
		abstract public function handle();

	}
}
