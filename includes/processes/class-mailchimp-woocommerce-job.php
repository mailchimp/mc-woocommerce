<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_Job' ) ) {
	abstract class Mailchimp_Woocommerce_Job {

		private $attempts = 0;
        public $prepend_to_queue = false;

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
