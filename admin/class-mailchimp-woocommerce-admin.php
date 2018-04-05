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
            'MailChimp - WooCommerce Setup',
            'MailChimp',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" enable-background="new 0 0 150 150"><style type="text/css">.st0{fill:#231F20;} .st1{fill:none;stroke:#231F20;stroke-width:0;stroke-linecap:round;stroke-linejoin:round;}</style><path class="st0" d="M146.157 97.673c-1.033-2.206-3.002-3.728-5.473-4.312-.826-3.773-1.969-5.626-2.074-5.901.436-.496.858-.996.951-1.112 3.49-4.328 1.212-10.669-4.752-12.163-3.356-3.229-6.392-4.746-8.885-5.992-2.391-1.195-1.436-.726-3.679-1.738-.595-2.915-.792-9.699-1.74-14.462-.851-4.284-2.565-7.385-5.209-9.425-1.058-2.291-2.541-4.599-4.329-6.298 8.317-12.75 10.506-25.343 4.415-31.938-2.713-2.938-6.742-4.332-11.559-4.332-6.783 0-15.127 2.766-23.553 7.883 0 0-5.483-4.413-5.602-4.507-23.466-18.478-89.826 63.181-66.401 81.026l6.047 4.621c-3.796 10.561 1.483 23.149 12.486 27.191 2.432.894 5.07 1.328 7.803 1.175 0 0 17.776 32.598 55.279 32.609 43.385.013 54.427-42.429 54.543-42.811 0 0 3.516-5.196 1.729-9.515zm-136.084-18.674c-4.755-8.024 3.517-24.505 9.404-33.873 14.548-23.153 38.762-41.424 49.761-38.815l3.025-1.159 8.269 6.989c5.681-3.411 12.919-6.889 19.686-7.579-4.118.927-9.137 3.062-15.082 6.7-.145.084-14.069 9.481-22.576 17.92-4.636 4.598-23.252 26.922-23.237 26.903 3.402-6.439 5.644-9.599 11.03-16.373 3.046-3.831 6.297-7.557 9.624-10.997 1.545-1.597 3.106-3.133 4.672-4.589 1.076-1.001 2.155-1.965 3.23-2.885.495-.424.99-.838 1.484-1.243l.002-.002-10.911-9.01.578 4.038 7.931 6.987s-7.018 4.725-10.509 7.703c-13.992 11.939-27.722 30.268-32.832 48.109l.244-.009c-2.545 1.402-5.07 3.652-7.276 6.713-.06-.016-5.711-4.163-6.515-5.529zm23.141 33.514c-8.382 0-15.177-7.153-15.177-15.978 0-8.824 6.795-15.978 15.177-15.978 2.173 0 4.239.481 6.108 1.347 0 0 3.226 1.627 4.133 9.313.945-2.402 1.421-4.373 1.421-4.373 1.081 3.307 1.635 6.784 1.418 10.3.897-1.193 1.859-3.44 1.859-3.44 1.668 9.82-5.498 18.809-14.939 18.809zm18.682-56.429s6.527-12.408 20.869-20.615c-1.068-.172-3.679.161-4.139.215 2.606-2.241 7.446-3.737 10.79-4.417-.979-.623-3.31-.78-4.467-.811-.342-.009-.338-.008-.743.01 3.146-1.759 8.979-2.791 14.281-1.859-.666-.885-2.178-1.53-3.237-1.844l-.508-.131.399-.092c3.194-.615 6.926.049 9.878 1.234-.335-.778-1.154-1.684-1.77-2.258-.063-.059-.431-.323-.431-.323 3.088.64 6.047 1.987 8.275 3.517-.303-.585-1.052-1.569-1.569-2.107 2.951.844 6.263 2.949 7.683 5.965l.135.344-.001.002c-5.591-4.299-21.911-3.081-38.249 7.508-7.481 4.849-12.967 10.152-17.195 15.662zm88.165 48.999c-.196.384-2.252 11.518-14.017 20.762-14.854 11.673-34.371 10.491-41.743 3.95-3.938-3.683-5.642-8.951-5.642-8.951s-.446 2.968-.522 4.134c-2.97-5.052-2.719-11.222-2.719-11.222s-1.585 2.955-2.31 4.61c-2.186-5.564-1.057-11.31-1.057-11.31l-1.729 2.578s-.811-6.297 1.18-11.543c2.128-5.605 6.254-9.677 7.07-10.184-3.131-.995-6.737-3.848-6.743-3.853 0 0 1.434.095 2.431-.134 0 0-6.329-4.534-7.439-11.472.917 1.133 2.841 2.413 2.841 2.413-.623-1.816-1-5.855-.418-9.83l.002-.004c1.197-7.594 7.456-12.539 14.546-12.477 7.548.067 12.605 1.651 18.933-4.184 1.339-1.235 2.408-2.3 4.288-2.717.198-.044.69-.25 1.699-.25 1.021 0 2.004.23 2.903.765 3.432 2.043 4.172 7.37 4.541 11.276 1.364 14.489.812 11.909 6.676 14.896 2.797 1.422 5.941 2.775 9.518 6.604l.027.031h.043c3.016.072 4.571 2.447 3.18 4.176-10.122 12.088-24.26 17.877-40.016 18.361l-2.122.051c-6.363.193-8.435 8.425-4.443 13.376 2.523 3.129 7.378 4.157 11.373 4.173l.056-.02c17.227.349 34.534-11.841 37.523-18.563l.204-.476c-.693.812-17.472 16.61-37.863 16.038 0 0-2.229-.046-4.328-.535-2.768-.644-4.871-1.86-5.676-4.619 1.692.339 3.833.556 6.319.556 14.718 0 25.324-6.691 24.216-6.781l-.16.027c-1.716.398-19.408 7.251-30.593 3.738.028-.341.079-.674.159-.971.995-3.331 2.763-2.866 5.62-2.99 10.204-.34 18.439-2.906 24.609-5.835 6.581-3.124 11.596-7.15 13.403-9.182 2.344 3.946 2.33 9.013 2.33 9.013s.919-.321 2.136-.321c3.824 0 4.611 3.421 1.716 6.894zm-49.984 4.587l-.003-.051.003.051zm-.004-.068l-.005-.131c-.008-.219-.009-.447 0-.676-.008.249-.007.474 0 .676l.005.131zm.041.487l.002.02-.002-.02zm.005.041c.067.529.172.775.185.803-.076-.162-.141-.449-.185-.803zM68.852 11.59l2.019.654-1.633-5.932-.96 2.955zM77.898 13.784l-3.339-2.799 1.213 4.214c.667-.457 1.378-.931 2.126-1.415zM90.067 109.471l.005.131-.005-.131zM90.067 108.795c-.009.229-.009.457 0 .676-.007-.202-.008-.426 0-.676z"/><path class="st1" d="M90.074 109.619l.003.051M90.116 110.109l-.002-.02"/><path class="st0" d="M90.303 110.932c-.013-.028-.118-.274-.185-.803.044.354.109.641.185.803zM108.26 67.645c1.73-.15 3.413.154 4.844.871-.11-4.12-2.509-8.759-4.321-8.131l-.002.001c-1.06.346-1.238 2.207-1.216 3.332.052 1.352.26 2.601.695 3.927zM74.439 16.128l-6.888-2.103 4.552 3.81c.663-.5 1.448-1.078 2.336-1.707zM91.917 73.511c1.355.527 2.272.932 2.528.633.129-.146.043-.498-.275-.964-.841-1.231-2.45-2.283-3.883-2.81-3.272-1.218-7.101-.527-9.889 1.697-1.386 1.126-2 2.286-1.366 2.381.389.058 1.199-.279 2.343-.715 4.521-1.711 7.075-1.515 10.541-.221zM92.427 77.611c.843.057 1.408.107 1.539-.122.291-.493-1.945-2.186-5-1.667-.381.058-.733.176-1.08.269-.127.032-.249.076-.367.126-.751.316-1.402.658-2.033 1.262-.724.7-.929 1.349-.72 1.509.206.164.707-.079 1.481-.392 2.595-1.079 4.43-1.107 6.18-.985zM23.713 97.473v-.001zM38.611 91.078c.798 1.13.544 1.789.871 2.109.118.116.287.152.455.084.452-.187.675-.907.717-1.409v-.003c.114-1.207-.524-2.564-1.37-3.526l-.002-.003c-1.101-1.278-2.834-2.264-4.798-2.568-1.789-.278-3.55.078-4.041.225l.002-.001c-.253.073-.567.14-.836.245-4.812 1.877-6.896 6.571-5.897 11.241.247 1.129.752 2.399 1.54 3.233l.002.003c.985 1.065 2.065.861 1.604-.13-.11-.274-.705-1.396-.785-3.426-.082-2.089.403-4.259 1.739-5.917.992-1.213 2.211-1.757 2.353-1.836l.541-.243.258-.085c.613-.173.265-.098.886-.229h-.001c3.31-.701 5.701.736 6.76 2.236zM37.115 94.54c-.322-.255-.472-.474-.561-.809-.132-.612-.063-.967.459-1.334.402-.276.726-.408.727-.582.018-.328-1.328-.665-2.269.261-.782.832-1.028 2.568.218 3.921 1.388 1.498 3.546 1.851 3.883 3.734.044.267.077.564.054.857.005.337-.108.821-.113.852-.417 1.819-2.144 3.555-4.99 3.112-.524-.076-.864-.141-.965.004-.219.306 1 1.701 3.233 1.65 3.189-.067 5.826-3.309 5.197-6.931-.569-3.157-3.707-3.806-4.873-4.736z"/><ellipse transform="matrix(.154 -.988 .988 .154 22.267 174.296)" class="st0" cx="112.928" cy="74.143" rx="2.17" ry="1.582"/><ellipse transform="matrix(.481 -.877 .877 .481 -12.182 132.698)" class="st0" cx="105.926" cy="76.632" rx="1.788" ry="2.452"/><path class="st0" d="M127.003 139.241h.687v1.776h.68v-1.776h.696v-.561h-2.063zM130.669 140.026l-.425-1.345h-.97v2.337h.621v-1.687l.507 1.687h.529l.507-1.692v1.692h.62v-2.337h-.97z"/></svg>')
        );
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

                if (isset($_POST['mc_action']) && in_array($_POST['mc_action'], array('view_log', 'remove_log'))) {
                    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=logs');
                    exit();
                }

                $data = array(
                    'mailchimp_logging' => isset($input['mailchimp_logging']) ? $input['mailchimp_logging'] : 'none',
                );
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
			if (!$profile) {
			    add_settings_error('mailchimp_store_settings', '', 'API Key Invalid');
            }
		}

		// tell our reporting system whether or not we had a valid ping.
		$this->setData('validation.api.ping', $valid);

		$data['active_tab'] = $valid ? 'store_info' : 'api_key';

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
        add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a contact email and a physical mailing address.');
    }

    /**
     *
     */
    protected function addInvalidPhoneAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a valid phone number for your store.');
    }

    /**
     *
     */
    protected function addInvalidStoreNameAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'MailChimp for WooCommerce requires a Store Name to connect your store.');
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

	/**
	 * Start the sync
	 */
	private function startSync()
	{
	    $coupon_sync = new MailChimp_WooCommerce_Process_Coupons();
	    wp_queue($coupon_sync);

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
