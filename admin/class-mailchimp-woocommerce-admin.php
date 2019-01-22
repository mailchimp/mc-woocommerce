<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class MailChimp_WooCommerce_Admin extends MailChimp_WooCommerce_Options {

	/**
	 * @return MailChimp_WooCommerce_Admin
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
        add_menu_page(
            'Mailchimp - WooCommerce Setup',
            'Mailchimp',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'), 'data:image/svg+xml;base64,'.$this->mailchimp_svg()
        );
	}

    /**
     * @return string
     */
    protected function mailchimp_svg()
    {
        return base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52.03 55"><defs><style>.cls-1{fill:#fff;}</style></defs><title>Asset 1</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M11.64,28.54a4.75,4.75,0,0,0-1.17.08c-2.79.56-4.36,2.94-4.05,6a6.24,6.24,0,0,0,5.72,5.21,4.17,4.17,0,0,0,.8-.06c2.83-.48,3.57-3.55,3.1-6.57C15.51,29.83,13.21,28.63,11.64,28.54Zm2.77,8.07a1.17,1.17,0,0,1-1.1.55,1.53,1.53,0,0,1-1.37-1.58A4,4,0,0,1,12.23,34a1.44,1.44,0,0,0-.55-1.74,1.48,1.48,0,0,0-1.12-.21,1.44,1.44,0,0,0-.92.64,3.39,3.39,0,0,0-.34.79l0,.11c-.13.34-.33.45-.47.43s-.16-.05-.21-.21a3,3,0,0,1,.78-2.55,2.46,2.46,0,0,1,2.11-.76,2.5,2.5,0,0,1,1.91,1.39,3.19,3.19,0,0,1-.23,2.82l-.09.2A1.16,1.16,0,0,0,13,36a.74.74,0,0,0,.63.32,1.38,1.38,0,0,0,.34,0c.15,0,.3-.07.39,0A.24.24,0,0,1,14.41,36.61Z"/><path class="cls-1" d="M51,33.88a3.84,3.84,0,0,0-1.15-1l-.11-.37-.14-.42a5.57,5.57,0,0,0,.5-3.32,5.43,5.43,0,0,0-1.54-3,10.09,10.09,0,0,0-4.24-2.26c0-.67,0-1.43-.06-1.9a12.83,12.83,0,0,0-.49-3.25,10.46,10.46,0,0,0-1.3-2.92c2.14-2.56,3.29-5.21,3.29-7.57,0-3.83-3-6.3-7.59-6.3a19.3,19.3,0,0,0-7.22,1.6l-.34.14L28.7,1.52A6.31,6.31,0,0,0,24.43,0,14.07,14.07,0,0,0,17.6,2.2a36.93,36.93,0,0,0-6.78,5.21c-4.6,4.38-8.3,9.63-9.91,14A12.51,12.51,0,0,0,0,26.54a6.16,6.16,0,0,0,2.13,4.4l.78.66A10.44,10.44,0,0,0,2.74,35a9.36,9.36,0,0,0,3.21,6,10,10,0,0,0,5.13,2.43,20.19,20.19,0,0,0,7.31,8A23.33,23.33,0,0,0,30.17,55H31a23.27,23.27,0,0,0,12-3.16,19.1,19.1,0,0,0,7.82-9.06l0,0A16.89,16.89,0,0,0,52,37.23,5.17,5.17,0,0,0,51,33.88Zm-1.78,8.21c-3,7.29-10.3,11.35-19,11.09-8.06-.24-14.94-4.5-18-11.43a7.94,7.94,0,0,1-5.12-2.06,7.56,7.56,0,0,1-2.61-4.85A8.31,8.31,0,0,1,5,31L3.32,29.56C-4.42,23,19.77-3.86,27.51,2.89l2.64,2.58,1.44-.61c6.79-2.81,12.3-1.45,12.3,3,0,2.33-1.48,5.05-3.86,7.52a7.54,7.54,0,0,1,2,3.48,11,11,0,0,1,.42,2.82c0,1,.09,3.16.09,3.2l1,.27A8.64,8.64,0,0,1,47.2,27a3.66,3.66,0,0,1,1.06,2.06A4,4,0,0,1,47.55,32,10.15,10.15,0,0,1,48,33.08c.2.64.35,1.18.37,1.25.74,0,1.89.85,1.89,2.89A15.29,15.29,0,0,1,49.18,42.09Z"/><path class="cls-1" d="M48,36a1.36,1.36,0,0,0-.86-.16,11.76,11.76,0,0,0-.82-2.78A17.89,17.89,0,0,1,40.45,36a23.64,23.64,0,0,1-7.81.84c-1.69-.14-2.81-.63-3.23.74a18.3,18.3,0,0,0,8,.81.14.14,0,0,1,.16.13.15.15,0,0,1-.09.15s-3.14,1.46-8.14-.08a2.58,2.58,0,0,0,1.83,1.91,8.24,8.24,0,0,0,1.44.39c6.19,1.06,12-2.47,13.27-3.36.1-.07.16,0,.08.12l-.13.18c-1.59,2.06-5.88,4.44-11.45,4.44-2.43,0-4.86-.86-5.75-2.17-1.38-2-.07-5,2.24-4.71l1,.11a21.13,21.13,0,0,0,10.5-1.68c3.15-1.46,4.34-3.07,4.16-4.37A1.87,1.87,0,0,0,46,28.34a6.8,6.8,0,0,0-3-1.41c-.5-.14-.84-.23-1.2-.35-.65-.21-1-.39-1-1.61,0-.53-.12-2.4-.16-3.16-.06-1.35-.22-3.19-1.36-4a1.92,1.92,0,0,0-1-.31,1.86,1.86,0,0,0-.58.06,3.07,3.07,0,0,0-1.52.86,5.24,5.24,0,0,1-4,1.32c-.8,0-1.65-.16-2.62-.22l-.57,0a5.22,5.22,0,0,0-5,4.57c-.56,3.83,2.22,5.81,3,7a1,1,0,0,1,.22.52.83.83,0,0,1-.28.55h0a9.8,9.8,0,0,0-2.16,9.2,7.59,7.59,0,0,0,.41,1.12c2,4.73,8.3,6.93,14.43,4.93a15.06,15.06,0,0,0,2.33-1,12.23,12.23,0,0,0,3.57-2.67,10.61,10.61,0,0,0,3-5.82C48.6,36.7,48.33,36.23,48,36Zm-8.25-7.82c0,.5-.31.91-.68.9s-.66-.42-.65-.92.31-.91.68-.9S39.72,27.68,39.71,28.18Zm-1.68-6c.71-.12,1.06.62,1.32,1.85a3.64,3.64,0,0,1-.05,2,4.14,4.14,0,0,0-1.06,0,4.13,4.13,0,0,1-.68-1.64C37.29,23.23,37.31,22.34,38,22.23Zm-2.4,6.57a.82.82,0,0,1,1.11-.19c.45.22.69.67.53,1a.82.82,0,0,1-1.11.19C35.7,29.58,35.47,29.13,35.63,28.8Zm-2.8-.37c-.07.11-.23.09-.57.06a4.24,4.24,0,0,0-2.14.22,2,2,0,0,1-.49.14.16.16,0,0,1-.11,0,.15.15,0,0,1-.05-.12.81.81,0,0,1,.32-.51,2.41,2.41,0,0,1,1.27-.53,1.94,1.94,0,0,1,1.75.57A.19.19,0,0,1,32.83,28.43Zm-5.11-1.26c-.12,0-.17-.07-.19-.14s.28-.56.62-.81a3.6,3.6,0,0,1,3.51-.42A3,3,0,0,1,33,26.87c.12.2.15.35.07.44s-.44,0-.95-.24a4.18,4.18,0,0,0-2-.43A21.85,21.85,0,0,0,27.71,27.17Z"/><path class="cls-1" d="M35.5,13.29c.1,0,.16-.15.07-.2a11,11,0,0,0-4.69-1.23.09.09,0,0,1-.07-.14,4.78,4.78,0,0,1,.88-.89.09.09,0,0,0-.06-.16,12.46,12.46,0,0,0-5.61,2,.09.09,0,0,1-.13-.09,6.16,6.16,0,0,1,.59-1.45.08.08,0,0,0-.11-.11A22.79,22.79,0,0,0,20,16.24a.09.09,0,0,0,.12.13A19.53,19.53,0,0,1,27,13.32,19.1,19.1,0,0,1,35.5,13.29Z"/><path class="cls-1" d="M28.34,6.42S26.23,4,25.6,3.8C21.69,2.74,13.24,8.57,7.84,16.27,5.66,19.39,2.53,24.9,4,27.74a11.43,11.43,0,0,0,1.79,1.72A6.65,6.65,0,0,1,10,26.78,34.21,34.21,0,0,1,20.8,11.62,55.09,55.09,0,0,1,28.34,6.42Z"/></g></g></svg>');
    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);
		return array_merge($settings_link, $links);
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
                // remove all the pointers to be sure
                $service = new MailChimp_Service();
                $service->removePointers(true, true);

                $this->startSync();
                $this->showSyncStartedMessage();
                $this->setData('sync.config.resync', true);
				break;

            case 'logs':

                if (isset($_POST['log_file']) && !empty($_POST['log_file'])) {
                    set_site_transient('mailchimp-woocommerce-view-log-file', $_POST['log_file'], 30);
                }
                
                $data = array(
                    'mailchimp_logging' => isset($input['mailchimp_logging']) ? $input['mailchimp_logging'] : 'none',
                );

                if (isset($_POST['mc_action']) && in_array($_POST['mc_action'], array('view_log', 'remove_log'))) {
                    $path = 'options-general.php?page=mailchimp-woocommerce&tab=logs';
                    wp_redirect($path);
                    exit();
                }

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
			'mailchimp_api_key' => isset($input['mailchimp_api_key']) ? trim($input['mailchimp_api_key']) : false,
			'mailchimp_debugging' => isset($input['mailchimp_debugging']) ? $input['mailchimp_debugging'] : false,
			'mailchimp_account_info_id' => null,
			'mailchimp_account_info_username' => null,
		);

        if (($failure = mailchimp_woocommerce_check_if_http_worker_fails())) {
            unset($data['mailchimp_api_key']);
            $data['active_tab'] = 'api_key';
            $data['api_ping_error'] = $failure;
            mailchimp_error('admin@validateCanUseHttpWorker', $failure);
            return $data;
        }

		$api = new MailChimp_WooCommerce_MailChimpApi($data['mailchimp_api_key']);

		try {
		    $profile = $api->ping(true, true);
            // tell our reporting system whether or not we had a valid ping.
            $this->setData('validation.api.ping', true);
            $data['active_tab'] = 'store_info';
            if (isset($profile) && is_array($profile) && array_key_exists('account_id', $profile)) {
                $data['mailchimp_account_info_id'] = $profile['account_id'];
                $data['mailchimp_account_info_username'] = $profile['username'];
            }
            $data['api_ping_error'] = false;
        } catch (Exception $e) {
            unset($data['mailchimp_api_key']);
            $data['active_tab'] = 'api_key';
            $data['api_ping_error'] = $e->getCode().' :: '.$e->getMessage().' on '.$e->getLine().' in '.$e->getFile();
            mailchimp_error('admin@validatePostApiKey', $e->getCode().' :: '.$e->getMessage().' on '.$e->getLine().' in '.$e->getFile());
            add_settings_error('mailchimp_store_settings', $e->getCode(), $e->getMessage());
            return $data;
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
		$data = $this->compileStoreInfoData($input);

		if (!$this->hasValidStoreInfo($data)) {

		    if ($this->hasInvalidStoreAddress($data)) {
		        $this->addInvalidAddressAlert();
            }

            if ($this->hasInvalidStorePhone($data)) {
		        $this->addInvalidPhoneAlert();
            }

            if ($this->hasInvalidStoreName($data)) {
		        $this->addInvalidStoreNameAlert();
            }

			$this->setData('validation.store_info', false);

            $data['active_tab'] = 'store_info';

			return array();
		}

		$this->setData('validation.store_info', true);

        $data['active_tab'] = 'campaign_defaults';

		if ($this->hasValidMailChimpList()) {
			$this->syncStore(array_merge($this->getOptions(), $data));
		}

		return $data;
	}

    /**
     * @param $input
     * @return array
     */
	protected function compileStoreInfoData($input)
    {
        return array(
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
    }

    /**
     * @param array $data
     * @return array|bool
     */
	protected function hasInvalidStoreAddress($data)
    {
        $address_keys = array(
            'admin_email',
            'store_city',
            'store_state',
            'store_postal_code',
            'store_country',
            'store_street'
        );

        $invalid = array();
        foreach ($address_keys as $address_key) {
            if (empty($data[$address_key])) {
                $invalid[] = $address_key;
            }
        }
        return empty($invalid) ? false : $invalid;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasInvalidStorePhone($data)
    {
        if (empty($data['store_phone']) || strlen($data['store_phone']) <= 6) {
            return true;
        }

        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasInvalidStoreName($data)
    {
        if (empty($data['store_name'])) {
            return true;
        }
        return false;
    }

    /**
     *
     */
	protected function addInvalidAddressAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'As part of the Mailchimp Terms of Use, we require a contact email and a physical mailing address.');
    }

    /**
     *
     */
    protected function addInvalidPhoneAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'As part of the Mailchimp Terms of Use, we require a valid phone number for your store.');
    }

    /**
     *
     */
    protected function addInvalidStoreNameAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'Mailchimp for WooCommerce requires a Store Name to connect your store.');
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

			return array('active_tab' => 'campaign_defaults');
		}

		$this->setData('validation.campaign_defaults', true);

        $data['active_tab'] = 'newsletter_settings';

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
			'mailchimp_auto_subscribe' => isset($input['mailchimp_auto_subscribe']) ? (bool) $input['mailchimp_auto_subscribe'] : $this->getOption('mailchimp_auto_subscribe', '0'),
			'mailchimp_checkbox_defaults' => $checkbox,
			'mailchimp_checkbox_action' => isset($input['mailchimp_checkbox_action']) ? $input['mailchimp_checkbox_action'] : $this->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form'),
            'mailchimp_product_image_key' => isset($input['mailchimp_product_image_key']) ? $input['mailchimp_product_image_key'] : 'medium',
        );

		if ($data['mailchimp_list'] === 'create_new') {
			$data['mailchimp_list'] = $this->createMailChimpList(array_merge($this->getOptions(), $data));
		}

		// as long as we have a list set, and it's currently in MC as a valid list, let's sync the store.
		if (!empty($data['mailchimp_list']) && $this->api()->hasList($data['mailchimp_list'])) {

            $this->setData('validation.newsletter_settings', true);

			// sync the store with MC
			$this->syncStore(array_merge($this->getOptions(), $data));

			// start the sync automatically if the sync is false
			if ((bool) $this->getData('sync.started_at', false) === false) {
				$this->startSync();
				$this->showSyncStartedMessage();
			}

            $data['active_tab'] = 'sync';

            return $data;
		}

        $this->setData('validation.newsletter_settings', false);

        $data['active_tab'] = 'newsletter_settings';

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
     * @param null $data
     * @param bool $throw_if_not_valid
     * @return array|bool|mixed|null|object
     * @throws Exception
     */
	public function hasValidApiKey($data = null, $throw_if_not_valid = false)
	{
		if (!$this->validateOptions(array('mailchimp_api_key'), $data)) {
			return false;
		}

		if (($pinged = $this->getCached('api-ping-check', null)) === null) {
            if (($pinged = $this->api()->ping(false, $throw_if_not_valid === true))) {
                $this->setCached('api-ping-check', true, 120);
            }
		}

		return $pinged;
	}

    /**
     * @return array|bool|mixed|null|object
     * @throws Exception
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_ServerError
     */
	public function hasValidMailChimpList()
	{
		if (!$this->hasValidApiKey()) {
			add_settings_error('mailchimp_api_key', '', 'You must supply your Mailchimp API key to pull the lists.');
			return false;
		}

		if (!($this->validateOptions(array('mailchimp_list')))) {
			return $this->api()->getLists(true);
		}

		return $this->api()->hasList($this->getOption('mailchimp_list'));
	}


    /**
     * @return array|bool|mixed|null|object
     * @throws Exception
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

        $list_id = $this->array_get($data, 'mailchimp_list', false);
        $site_url = $this->getUniqueStoreID();

		if (empty($list_id) || empty($site_url)) {
		    return false;
        }

		$new = false;

		if (!($store = $this->api()->getStore($site_url))) {
			$new = true;
			$store = new MailChimp_WooCommerce_Store();
		}

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

        // don't know why we did this before
        //$store->setEmailAddress($this->array_get($data, 'campaign_from_email'));
        $store->setEmailAddress($this->array_get($data, 'admin_email'));

		$store->setAddress($this->address($data));
		$store->setPhone($this->array_get($data, 'store_phone'));
		$store->setListId($list_id);

		try {
			// let's create a new store for this user through the API
			$this->api()->$call($store);

			// apply extra meta for store created at
			$this->setData('errors.store_info', false);
			$this->setData($time_key, time());

			// on a new store push, we need to make sure we save the site script into a local variable.
			if ($new) {
                mailchimp_update_connected_site_script();
            }

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

	/**a:4:{s:19:"mailchimp_debugging";b:0;s:25:"mailchimp_account_info_id";N;s:31:"mailchimp_account_info_username";N;s:10:"active_tab";s:7:"api_key";}
	 * Start the sync
	 */
	private function startSync()
	{
	    mailchimp_flush_sync_pointers();

	    $coupon_sync = new MailChimp_WooCommerce_Process_Coupons();
	    mailchimp_handle_or_queue($coupon_sync);

		$job = new MailChimp_WooCommerce_Process_Products();
		$job->flagStartSync();
		mailchimp_handle_or_queue($job);
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
