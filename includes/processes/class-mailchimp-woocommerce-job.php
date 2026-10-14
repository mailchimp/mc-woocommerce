<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_Job' ) ) {
	abstract class Mailchimp_Woocommerce_Job {

		private $attempts = 0;
        public $prepend_to_queue = false;
		// unix time the job was first scheduled to run (queued time + delay).
		// serialized with the job, so it survives rate-limit retries.
		public $notified_at = null;
		// only live hook jobs send X-Object-Notified-At - never the initial sync.
		public $is_live_event = false;

		/**
		 * @return $this
		 */
		public function mark_live_event() {
			$this->is_live_event = true;
			return $this;
		}

		/**
		 * Stamps the first notification time only - later calls keep the original.
		 *
		 * @param int|null $timestamp
		 * @return $this
		 */
		public function set_notified_at( $timestamp ) {
			if ( empty( $this->notified_at ) && ! empty( $timestamp ) ) {
				$this->notified_at = (int) $timestamp;
			}
			return $this;
		}

		/**
		 * @return int|null
		 */
		public function get_notified_at() {
			return ! empty( $this->notified_at ) ? (int) $this->notified_at : null;
		}

		/**
		 * @param $attempts
		 */
		public function set_attempts( $attempts ) {
			$this->attempts = (int) $attempts;
		}

		/**
		 * @return int
		 */
		public function get_attempts( ) {
			return $this->attempts;
		}

		/**
		 * @param int $delay
		 */
		public function retry( $delay = 30 ) {
			$job = $this;
			if (null == $job->attempts) $job->set_attempts(0);
			$job->set_attempts($job->get_attempts() + 1);
			mailchimp_as_push($job, $delay);
		}

		/**
		 * @return $this
		 */
		protected function applyRateLimitedScenario()
		{
			mailchimp_set_transient('api-rate-limited', true );

			$this->retry();

			return $this;
		}
		
		/**
		 * Handle the job.
		 */
		abstract public function handle();

	}
}
