<?php

class MailChimp_WooCommerce_Fix_Duplicate_Store {

	protected $store;
	protected $has_old_integration     = false;
	protected $duplicate_store_problem = false;
	protected $deleted_stores          = array();
	protected $should_delete_duplicate = false;
	protected $should_delete_legacy    = false;

	/**
	 * FixDuplicatMailChimp_WooCommerce_Fix_Duplicate_StoreeStore constructor.
	 *
	 * @param $store_id
	 * @param bool     $delete_duplicate
	 * @param false    $delete_legacy
	 */
	public function __construct( $store_id, bool $delete_duplicate = false, $delete_legacy = false ) {
		$this->store                   = $store_id;
		$this->should_delete_duplicate = $delete_duplicate;
		$this->should_delete_legacy    = $delete_legacy;
	}

	/**
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function handle() {
		$this->deleted_stores = array();
		$url                  = rtrim( get_option( 'siteurl' ), '/' );
		$stores               = mailchimp_get_api()->stores();
		$compare_url          = $this->domain( $url );
		$public_key           = mailchimp_get_store_id();

		if ( is_array( $stores ) && ! empty( $stores ) ) {
			foreach ( $stores as $mc_store ) {
				/** @var MailChimp_WooCommerce_Store $mc_store */
				$store_url = $this->domain( $mc_store->getDomain() );
				$matched   = strtolower( $mc_store->getPlatform() ) === 'woocommerce';
				if ( $store_url === $compare_url ) {
					if ( $mc_store->getId() !== $public_key && $matched ) {
						$this->duplicate_store_problem = $mc_store;
						if ( $this->should_delete_duplicate ) {
							mailchimp_get_api()->deleteStore( $mc_store->getId() );
							MailChimp_WooCommerce_Admin::instance()->syncStore();
							$this->deleted_stores[] = $mc_store;
						}
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getDeletedStores() {
		return $this->deleted_stores;
	}

	/**
	 * @return bool|MailChimp_WooCommerce_Store
	 */
	public function hasDuplicateStoreProblem() {
		return $this->duplicate_store_problem;
	}

	/**
	 * @return bool|MailChimp_WooCommerce_Store
	 */
	public function hasOldIntegration() {
		return $this->has_old_integration;
	}

	/**
	 * @param $url
	 * @return string|string[]
	 */
	public function domain( $url ) {
		return str_replace(
			array( 'http://', 'https://', 'www.' ),
			'',
			rtrim( strtolower( trim( $url ) ), '/' )
		);
	}
}
