<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_Job' ) ) {
	abstract class Mailchimp_Woocommerce_Job {

		private $attempts = 0;
        public $prepend_to_queue = false;

        /** @var int|null First intended eligibility time, only for live-hook work. */
        public $eligible_at = null;

        /** @var int|null Request-local context; never serialized with a job. */
        private static $active_eligible_at = null;

        /** Preserve the first value, including through retry/reschedule. */
        public function set_eligible_at($timestamp)
        {
            if ($this->eligible_at === null && is_int($timestamp) && $timestamp > 0) {
                $this->eligible_at = $timestamp;
            }
            return $this;
        }

        /** Old serialized jobs and malformed metadata have no trusted timestamp. */
        public function get_eligible_at()
        {
            if (!empty($this->is_full_sync)) {
                return null;
            }
            return is_int($this->eligible_at) && $this->eligible_at > 0 ? $this->eligible_at : null;
        }

        public static function active_eligible_at()
        {
            return self::$active_eligible_at;
        }

        /** Scope metadata to this call, including exceptions and nested execution. */
        public static function with_eligible_at($timestamp, $callback, $args = array())
        {
            $previous = self::$active_eligible_at;
            self::$active_eligible_at = is_int($timestamp) && $timestamp > 0 ? $timestamp : null;
            try {
                return call_user_func_array($callback, $args);
            } finally {
                self::$active_eligible_at = $previous;
            }
        }

        /** Execute even historical jobs in their own context to prevent leakage. */
        public function handle_with_context()
        {
            return self::with_eligible_at($this->get_eligible_at(), array($this, 'handle'));
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
