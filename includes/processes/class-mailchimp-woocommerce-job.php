<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_Job' ) ) {
	abstract class Mailchimp_Woocommerce_Job {

	    public $should_kill_queue_listener = false;

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
		 * Handle the job.
		 */
		abstract public function handle();

	}
}
