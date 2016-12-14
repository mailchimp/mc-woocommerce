<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/admin
 * @author     Ryan Hungate <ryan@mailchimp.com>
 */
class MailChimp_Woocommerce_Admin extends MailChimp_Woocommerce_Options {

	/**
	 * @return MailChimp_Woocommerce_Admin
	 */
	public static function connect()
	{
		$env = mailchimp_environment_variables();

		return new self('mailchimp-woocommerce', $env->version);
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */

	public function add_plugin_admin_menu() {
		/*
         *  Documentation : http://codex.wordpress.org/Administration_Menus
         */
		add_options_page( 'MailChimp - WooCommerce Setup', 'MailChimp', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'));
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		/*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);

		return array_merge($settings_link, $links);
	}

	/**
	 * Admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_menu( array(
			'id'    => 'mailchimp-woocommerce',
			'title' => __('MailChimp', 'mailchimp-woocommerce' ),
			'href'  => '#',
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-api-key',
			'title'  => __('API Key', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=api_key'), 'mc-api-key'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-store-info',
			'title'  => __('Store Info', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=store_info'), 'mc-store-info'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-campaign-defaults',
			'title'  => __('Campaign Defaults', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=campaign_defaults'), 'mc-campaign-defaults'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-newsletter-settings',
			'title'  => __('Newsletter Settings', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=newsletter_settings'), 'mc-newsletter-settings'),
		));

		// only display this button if the data is not syncing and we have a valid api key
		if ((bool) $this->getOption('mailchimp_list', false) && (bool) $this->getData('sync.syncing', false) === false) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'mailchimp-woocommerce',
				'id'     => 'mailchimp-woocommerce-sync',
				'title'  => __('Sync', 'mailchimp-woocommerce'),
				'href'   => wp_nonce_url(admin_url('?mailchimp-woocommerce[action]=sync&mailchimp-woocommerce[action]=sync'), 'mc-sync'),
			));
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_setup_page() {
		include_once( 'partials/mailchimp-woocommerce-admin-tabs.php' );
	}

	/**
	 *
	 */
	public function options_update() {

		$this->handle_abandoned_cart_table();

		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	/**
	 * Depending on the version we're on we may need to run some sort of migrations.
	 */
	public function update_db_check() {
		// grab the current version set in the plugin variables
		$version = mailchimp_environment_variables()->version;

		// grab the saved version or default to 1.0.3 since that's when we first did this.
		$saved_version = get_site_option('mailchimp_woocommerce_version', '1.0.3');

		// if the saved version is less than the current version
		if (version_compare($version, $saved_version) > 0) {
			// resave the site option so this only fires once.
			update_site_option('mailchimp_woocommerce_version', $version);
		}
	}

	/**
	 * We need to do a tidy up function on the mailchimp_carts table to
	 * remove anything older than 30 days.
	 *
	 * Also if we don't have the configuration set, we need to create the table.
	 */
	protected function handle_abandoned_cart_table()
	{
		global $wpdb;

		if (get_site_option('mailchimp_woocommerce_db_mailchimp_carts', false)) {
			// need to tidy up the mailchimp_cart table and make sure we don't have anything older than 30 days old.
			$date = gmdate( 'Y-m-d H:i:s', strtotime(date ("Y-m-d") ."-30 days"));
			$sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mailchimp_carts WHERE created_at <= %s", $date);
			$wpdb->query($sql);
		} else {

			// create the table for the first time now.
			$charset_collate = $wpdb->get_charset_collate();
			$table = "{$wpdb->prefix}mailchimp_carts";

			$sql = "CREATE TABLE IF NOT EXISTS $table (
				id VARCHAR (255) NOT NULL,
				email VARCHAR (100) NOT NULL,
				user_id INT (11) DEFAULT NULL,
                cart text NOT NULL,
                created_at datetime NOT NULL
				) $charset_collate;";

			if (($result = $wpdb->query($sql)) > 0) {
				update_site_option('mailchimp_woocommerce_db_mailchimp_carts', true);
			}
		}
	}

	/**
	 * @param $input
	 * @return array
	 */
	public function validate($input) {

		$active_tab = isset($input['mailchimp_active_tab']) ? $input['mailchimp_active_tab'] : null;

		if (empty($active_tab)) {
			return $this->getOptions();
		}

		switch ($active_tab) {

			case 'api_key':
				$data = $this->validatePostApiKey($input);
				break;

			case 'store_info':
				$data = $this->validatePostStoreInfo($input);
				break;

			case 'campaign_defaults' :
				$data = $this->validatePostCampaignDefaults($input);
				break;

			case 'newsletter_settings':
				$data = $this->validatePostNewsletterSettings($input);
				break;

			case 'sync':
				$this->startSync();
				$this->showSyncStartedMessage();
				break;
		}

		return (isset($data) && is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
	}

	/**
	 * STEP 1.
	 *
	 * Handle the 'api_key' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostApiKey($input)
	{
		$data = array(
			'mailchimp_api_key' => isset($input['mailchimp_api_key']) ? $input['mailchimp_api_key'] : false,
			'mailchimp_debugging' => isset($input['mailchimp_debugging']) ? $input['mailchimp_debugging'] : false,
			'mailchimp_account_info_id' => null,
			'mailchimp_account_info_username' => null,
		);

		$api = new MailChimp_WooCommerce_MailChimpApi($data['mailchimp_api_key']);

		$valid = true;

		if (empty($data['mailchimp_api_key']) || !($profile = $api->ping(true))) {
			unset($data['mailchimp_api_key']);
			$valid = false;
		}

		// tell our reporting system whether or not we had a valid ping.
		$this->setData('validation.api.ping', $valid);

		if ($valid && isset($profile) && is_array($profile) && array_key_exists('account_id', $profile)) {
			$data['mailchimp_account_info_id'] = $profile['account_id'];
			$data['mailchimp_account_info_username'] = $profile['username'];
		}

		return $data;
	}

	/**
	 * STEP 2.
	 *
	 * Handle the 'store_info' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostStoreInfo($input)
	{
		$data = array(

			// store basics
			'store_name' => trim((isset($input['store_name']) ? $input['store_name'] : get_option('blogname'))),
			'store_street' => isset($input['store_street']) ? $input['store_street'] : false,
			'store_city' => isset($input['store_city']) ? $input['store_city'] : false,
			'store_state' => isset($input['store_state']) ? $input['store_state'] : false,
			'store_postal_code' => isset($input['store_postal_code']) ? $input['store_postal_code'] : false,
			'store_country' => isset($input['store_country']) ? $input['store_country'] : false,
			'store_phone' => isset($input['store_phone']) ? $input['store_phone'] : false,

			// locale info
			'store_locale' => isset($input['store_locale']) ? $input['store_locale'] : false,
			'store_timezone' => isset($input['store_timezone']) ? $input['store_timezone'] : false,
			'store_currency_code' => isset($input['store_currency_code']) ? $input['store_currency_code'] : false,

			'admin_email' => isset($input['admin_email']) && is_email($input['admin_email']) ? $input['admin_email'] : $this->getOption('admin_email', false),
		);

		if (!$this->hasValidStoreInfo($data)) {

			if (empty($data['admin_email']) || empty($data['store_city']) || empty($data['store_state']) || empty($data['store_postal_code']) || empty($data['store_country']) || empty($data['store_street'])) {
				add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a contact email and a physical mailing address.');
			}

			if (empty($data['store_phone']) || strlen($data['store_phone']) <= 6) {
				add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a valid phone number for your store.');
			}

			if (empty($data['store_name'])) {
				add_settings_error('mailchimp_store_settings', '', 'MailChimp for WooCommerce requires a Store Name to connect your store.');
			}

			$this->setData('validation.store_info', false);
			return array();
		}

		$this->setData('validation.store_info', true);

		if ($this->hasValidMailChimpList()) {
			$this->syncStore(array_merge($this->getOptions(), $data));
		}

		return $data;
	}

	/**
	 * STEP 3.
	 *
	 * Handle the 'campaign_defaults' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostCampaignDefaults($input)
	{
		$data = array(
			'campaign_from_name' => isset($input['campaign_from_name']) ? $input['campaign_from_name'] : false,
			'campaign_from_email' => isset($input['campaign_from_email']) && is_email($input['campaign_from_email']) ? $input['campaign_from_email'] : false,
			'campaign_subject' => isset($input['campaign_subject']) ? $input['campaign_subject'] : get_option('blogname'),
			'campaign_language' => isset($input['campaign_language']) ? $input['campaign_language'] : 'en',
			'campaign_permission_reminder' => isset($input['campaign_permission_reminder']) ? $input['campaign_permission_reminder'] : 'You were subscribed to the newsletter from '.get_option('blogname'),
		);

		if (!$this->hasValidCampaignDefaults($data)) {
			$this->setData('validation.campaign_defaults', false);
			return array();
		}

		$this->setData('validation.campaign_defaults', true);

		return $data;
	}

	/**
	 * STEP 4.
	 *
	 * Handle the 'newsletter_settings' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostNewsletterSettings($input)
	{
		// default value.
		$checkbox = $this->getOption('mailchimp_checkbox_defaults', 'check');

		// see if it's posted in the form.
		if (isset($input['mailchimp_checkbox_defaults']) && !empty($input['mailchimp_checkbox_defaults'])) {
			$checkbox = $input['mailchimp_checkbox_defaults'];
		}

		$data = array(
			'mailchimp_list' => isset($input['mailchimp_list']) ? $input['mailchimp_list'] : $this->getOption('mailchimp_list', ''),
			'newsletter_label' => isset($input['newsletter_label']) ? $input['newsletter_label'] : $this->getOption('newsletter_label', 'Subscribe to our newsletter'),
			'mailchimp_auto_subscribe' => isset($input['mailchimp_auto_subscribe']) ? $input['mailchimp_auto_subscribe'] : $this->getOption('mailchimp_auto_subscribe', '0'),
			'mailchimp_checkbox_defaults' => $checkbox,
			'mailchimp_checkbox_action' => isset($input['mailchimp_checkbox_action']) ? $input['mailchimp_checkbox_action'] : $this->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form'),
		);

		if ($data['mailchimp_list'] === 'create_new') {
			$data['mailchimp_list'] = $this->createMailChimpList(array_merge($this->getOptions(), $data));
		}

		// as long as we have a list set, and it's currently in MC as a valid list, let's sync the store.
		if (!empty($data['mailchimp_list']) && $this->api()->hasList($data['mailchimp_list'])) {

			// sync the store with MC
			$this->syncStore(array_merge($this->getOptions(), $data));

			// start the sync automatically if the sync is false
			if ((bool) $this->getData('sync.started_at', false) === false) {
				$this->startSync();
				$this->showSyncStartedMessage();
			}
		}

		return $data;
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidStoreInfo($data = null)
	{
		return $this->validateOptions(array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'store_phone',
			'store_locale', 'store_timezone', 'store_currency_code',
			'store_phone',
		), $data);
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidCampaignDefaults($data = null)
	{
		return $this->validateOptions(array(
			'campaign_from_name', 'campaign_from_email', 'campaign_subject', 'campaign_language',
			'campaign_permission_reminder'
		), $data);
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidApiKey($data = null)
	{
		if (!$this->validateOptions(array('mailchimp_api_key'), $data)) {
			return false;
		}

		if (($pinged = $this->getCached('api-ping-check', null)) === null) {
			if (($pinged = $this->api()->ping())) {
				$this->setCached('api-ping-check', true, 120);
			}
			return $pinged;
		}
		return $pinged;
	}

	/**
	 * @return bool
	 */
	public function hasValidMailChimpList()
	{
		if (!$this->hasValidApiKey()) {
			add_settings_error('mailchimp_api_key', '', 'You must supply your MailChimp API key to pull the lists.');
			return false;
		}

		if (!($this->validateOptions(array('mailchimp_list')))) {
			return $this->api()->getLists(true);
		}

		return $this->api()->hasList($this->getOption('mailchimp_list'));
	}


	/**
	 * @return array|bool|mixed|null
	 */
	public function getAccountDetails()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		try {
			if (($account = $this->getCached('api-account-name', null)) === null) {
				if (($account = $this->api()->getProfile())) {
					$this->setCached('api-account-name', $account, 120);
				}
			}
			return $account;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @return array|bool
	 */
	public function getMailChimpLists()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		try {
			if (($pinged = $this->getCached('api-lists', null)) === null) {
				$pinged = $this->api()->getLists(true);
				if ($pinged) {
					$this->setCached('api-lists', $pinged, 120);
				}
				return $pinged;
			}
			return $pinged;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @return array|bool
	 */
	public function getListName()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!($list_id = $this->getOption('mailchimp_list', false))) {
			return false;
		}

		try {
			if (($lists = $this->getCached('api-lists', null)) === null) {
				$lists = $this->api()->getLists(true);
				if ($lists) {
					$this->setCached('api-lists', $lists, 120);
				}
			}

			return array_key_exists($list_id, $lists) ? $lists[$list_id] : false;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @return bool
	 */
	public function isReadyForSync()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!$this->getOption('mailchimp_list', false)) {
			return false;
		}

		if (!$this->api()->hasList($this->getOption('mailchimp_list'))) {
			return false;
		}

		if (!$this->api()->getStore($this->getUniqueStoreID())) {
			return false;
		}

		return true;
	}

	/**
	 * @param null|array $data
	 * @return bool|string
	 */
	private function createMailChimpList($data = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

		$required = array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'campaign_from_name',
			'campaign_from_email', 'campaign_subject', 'campaign_permission_reminder',
		);

		foreach ($required as $requirement) {
			if (!isset($data[$requirement]) || empty($data[$requirement])) {
				return false;
			}
		}

		$submission = new MailChimp_WooCommerce_CreateListSubmission();

		// allow the subscribers to choose preferred email type (html or text).
		$submission->setEmailTypeOption(true);

		// set the store name
		$submission->setName($data['store_name']);

		// set the campaign defaults
		$submission->setCampaignDefaults(
			$data['campaign_from_name'],
			$data['campaign_from_email'],
			$data['campaign_subject'],
			$data['campaign_language']
		);

		// set the permission reminder message.
		$submission->setPermissionReminder($data['campaign_permission_reminder']);

		if (isset($data['admin_email']) && !empty($data['admin_email'])) {
			$submission->setNotifyOnSubscribe($data['admin_email']);
			$submission->setNotifyOnUnSubscribe($data['admin_email']);
		}

		$submission->setContact($this->address($data));

		try {
			$response = $this->api()->createList($submission);

			$list_id = array_key_exists('id', $response) ? $response['id'] : false;

			$this->setData('errors.mailchimp_list', false);

			return $list_id;

		} catch (MailChimp_WooCommerce_Error $e) {
			$this->setData('errors.mailchimp_list', $e->getMessage());
			return false;
		}
	}

	/**
	 * @param null $data
	 * @return bool
	 */
	private function syncStore($data = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

		$site_url = $this->getUniqueStoreID();

		$new = false;

		if (!($store = $this->api()->getStore($site_url))) {
			$new = true;
			$store = new MailChimp_WooCommerce_Store();
		}

		$list_id = $this->array_get($data, 'mailchimp_list', false);
		$call = $new ? 'addStore' : 'updateStore';
		$time_key = $new ? 'store_created_at' : 'store_updated_at';

		$store->setId($site_url);
		$store->setPlatform('woocommerce');

		// set the locale data
		$store->setPrimaryLocale($this->array_get($data, 'store_locale', 'en'));
		$store->setTimezone($this->array_get($data, 'store_timezone', 'America\New_York'));
		$store->setCurrencyCode($this->array_get($data, 'store_currency_code', 'USD'));

		// set the basics
		$store->setName($this->array_get($data, 'store_name'));
		$store->setDomain(get_option('siteurl'));
		$store->setEmailAddress($this->array_get($data, 'campaign_from_email'));
		$store->setAddress($this->address($data));
		$store->setPhone($this->array_get($data, 'store_phone'));
		$store->setListId($list_id);

		try {
			// let's create a new store for this user through the API
			$this->api()->$call($store);

			// apply extra meta for store created at
			$this->setData('errors.store_info', false);
			$this->setData($time_key, time());

			return true;

		} catch (\Exception $e) {
			$this->setData('errors.store_info', $e->getMessage());
		}

		return false;
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_Address
	 */
	private function address(array $data)
	{
		$address = new MailChimp_WooCommerce_Address();

		if (isset($data['store_street']) && $data['store_street']) {
			$address->setAddress1($data['store_street']);
		}

		if (isset($data['store_city']) && $data['store_city']) {
			$address->setCity($data['store_city']);
		}

		if (isset($data['store_state']) && $data['store_state']) {
			$address->setProvince($data['store_state']);
		}

		if (isset($data['store_country']) && $data['store_country']) {
			$address->setCountry($data['store_country']);
		}

		if (isset($data['store_postal_code']) && $data['store_postal_code']) {
			$address->setPostalCode($data['store_postal_code']);
		}

		if (isset($data['store_name']) && $data['store_name']) {
			$address->setCompany($data['store_name']);
		}

		if (isset($data['store_phone']) && $data['store_phone']) {
			$address->setPhone($data['store_phone']);
		}

		$address->setCountryCode($this->array_get($data, 'store_currency_code', 'USD'));

		return $address;
	}

	/**
	 * @param array $required
	 * @param null $options
	 * @return bool
	 */
	private function validateOptions(array $required, $options = null)
	{
		$options = is_array($options) ? $options : $this->getOptions();

		foreach ($required as $requirement) {
			if (!isset($options[$requirement]) || empty($options[$requirement])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Start the sync
	 */
	private function startSync()
	{
		$job = new MailChimp_WooCommerce_Process_Products();
		$job->flagStartSync();
		wp_queue($job);
	}

	/**
	 * Show the sync started message right when they sync things.
	 */
	private function showSyncStartedMessage()
	{
		$text = 'Starting the sync process…<br/>'.
			'<p id="sync-status-message">Please hang tight while we work our mojo. Sometimes the sync can take a while, '.
			'especially on sites with lots of orders and/or products. You may refresh this page at '.
			'anytime to check on the progress.</p>';

		add_settings_error('mailchimp-woocommerce_notice', $this->plugin_name, __($text), 'updated');
	}
}
