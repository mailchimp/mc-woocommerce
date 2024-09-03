<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/includes
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class MailChimp_WooCommerce
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      MailChimp_WooCommerce_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * @var string
     */
    protected $environment = 'production';

    protected $is_configured;

    protected static $logging_config = null;

    /**
     * @return object
     */
    public static function getLoggingConfig()
    {
        if (is_object(static::$logging_config)) {
            return static::$logging_config;
        }

        $plugin_options = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce');
        $is_options = is_array($plugin_options);

        $api_key = $is_options && array_key_exists('mailchimp_api_key', $plugin_options) ?
            $plugin_options['mailchimp_api_key'] : false;

        $enable_logging = $is_options &&
            array_key_exists('mailchimp_debugging', $plugin_options) &&
            $plugin_options['mailchimp_debugging'];

        $account_id = $is_options && array_key_exists('mailchimp_account_info_id', $plugin_options) ?
            $plugin_options['mailchimp_account_info_id'] : false;

        $username = $is_options && array_key_exists('mailchimp_account_info_username', $plugin_options) ?
            $plugin_options['mailchimp_account_info_username'] : false;

        $api_key_parts = str_getcsv($api_key, '-');
        $data_center = isset($api_key_parts[1]) ? $api_key_parts[1] : 'us1';

        return static::$logging_config = (object)array(
            'enable_logging' => (bool)$enable_logging,
            'account_id' => $account_id,
            'username' => $username,
            'endpoint' => 'https://ecommerce.' . $data_center . '.list-manage.com/ecommerce/log',
        );
    }


    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @param string $environment
     * @param string $version
     *
     * @since    1.0.0
     */
    public function __construct($environment = 'production', $version = '1.0.0')
    {
        $this->plugin_name = 'mailchimp-woocommerce';
        $this->version = $version;
        $this->environment = $environment;
        $this->is_configured = mailchimp_is_configured();

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_gdpr_hooks();

        $this->activateMailChimpNewsletter();
        $this->activateMailChimpService();
        $this->applyQueryStringOverrides();
    }

    /**
     * @return void|bool
     */
    private function applyQueryStringOverrides()
    {
        // if we need to refresh the double opt in for any reason - just do it here.
        if ($this->queryStringEquals('mc_doi_refresh')) {
            try {
                $enabled_doi = mailchimp_list_has_double_optin(true);
            } catch (Exception $e) {
                mailchimp_error('mc.utils.doi_refresh', 'failed updating doi transient');
                return false;
            }
            mailchimp_log('mc.utils.doi_refresh', ($enabled_doi ? 'turned ON' : 'turned OFF'));
        }
        return;
    }

    /**
     * @param $key
     * @param string $value
     * @return bool
     */
    private function queryStringEquals($key, $value = '1')
    {
        return isset($_GET[$key]) && $_GET[$key] === $value;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - MailChimp_WooCommerce_Loader. Orchestrates the hooks of the plugin.
     * - MailChimp_WooCommerce_i18n. Defines internationalization functionality.
     * - MailChimp_WooCommerce_Admin. Defines all hooks for the admin area.
     * - MailChimp_WooCommerce_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        // fire up the loader
        $this->loader = new MailChimp_WooCommerce_Loader();

        // change up the queue to use the new rest api version
        $service = new MailChimp_WooCommerce_Rest_Api();
        $this->loader->add_action( 'rest_api_init', $service, 'register_routes');
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the MailChimp_WooCommerce_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new MailChimp_WooCommerce_i18n();
        $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Define the GDPR additions from Automattic.
     */
    private function define_gdpr_hooks()
    {
        $gdpr = new MailChimp_WooCommerce_Privacy();

        $this->loader->add_action('admin_init', $gdpr, 'privacy_policy');
        $this->loader->add_filter('wp_privacy_personal_data_exporters', $gdpr, 'register_exporter');
        $this->loader->add_filter('wp_privacy_personal_data_erasers', $gdpr, 'register_eraser');
    }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = MailChimp_WooCommerce_Admin::instance();

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Add menu item
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu', 71);
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_create_account_page', 72);

        // Add WooCommerce Navigation Bar
        // $this->loader->add_action('admin_menu', $plugin_admin, 'add_woocommerce_navigation_bar');

        // Add Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php');
		$this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

		// make sure we're listening for the admin init
        $this->loader->add_action('admin_init', $plugin_admin, 'options_update');
        $this->loader->add_action('admin_notices', $plugin_admin, 'initial_notice');
        // remove this for now
        //$this->loader->add_action('admin_notices', $plugin_admin, 'webook_initial_notice');
        $this->loader->add_action('admin_notices', $plugin_admin, 'action_scheduler_notice');
        
		// put the menu on the admin top bar.
		//$this->loader->add_action('admin_bar_menu', $plugin_admin, 'admin_bar', 100);

        $this->loader->add_action('plugins_loaded', $plugin_admin, 'update_db_check');
		$this->loader->add_action('upgrader_process_complete', $plugin_admin, 'plugin_upgrade_completed', 10, 2);
        $this->loader->add_action('init', $plugin_admin, 'update_plugin_check', 13);
        $this->loader->add_action('admin_init', $plugin_admin, 'setup_survey_form');
        $this->loader->add_action('admin_footer', $plugin_admin, 'inject_sync_ajax_call');

        // update MC store information when woocommerce general settings are saved
        $this->loader->add_action('woocommerce_settings_save_general', $plugin_admin, 'mailchimp_update_woo_settings');
        $this->loader->add_action('update_option_blogname', $plugin_admin, 'mailchimp_update_wordpress_title', 10, 2);

        // update MC store information if "WooCommerce Multi-Currency Extension" settings are saved
        if ( class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
            $this->loader->add_action('villatheme_support_woo-multi-currency', $plugin_admin, 'mailchimp_update_woo_settings');
        }

        // Mailchimp oAuth
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_oauth_start', $plugin_admin, 'mailchimp_woocommerce_ajax_oauth_start' );
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_oauth_status', $plugin_admin, 'mailchimp_woocommerce_ajax_oauth_status' );
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_oauth_finish', $plugin_admin, 'mailchimp_woocommerce_ajax_oauth_finish' );

        // Create new mailchimp Account methods
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_create_account_check_username', $plugin_admin, 'mailchimp_woocommerce_ajax_create_account_check_username' );
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_create_account_signup', $plugin_admin, 'mailchimp_woocommerce_ajax_create_account_signup' );
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_check_login_session', $plugin_admin, 'mailchimp_woocommerce_ajax_check_login_session' );
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_support_form', $plugin_admin, 'mailchimp_woocommerce_ajax_support_form' );

        // add Shop Manager capability to save options
        $this->loader->add_action('option_page_capability_mailchimp-woocommerce', $plugin_admin, 'mailchimp_woocommerce_option_page_capability');

        // set communications box status
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_communication_status', $plugin_admin, 'mailchimp_woocommerce_communication_status' );

        // set tower support status
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_tower_status', $plugin_admin, 'mailchimp_woocommerce_tower_status' );

        // Load log file via ajax
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_load_log_file', $plugin_admin, 'mailchimp_woocommerce_ajax_load_log_file' );

        // delete log file via ajax
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_delete_log_file', $plugin_admin, 'mailchimp_woocommerce_ajax_delete_log_file' );

        // send event to mailchimp
        $this->loader->add_action( 'wp_ajax_mailchimp_woocommerce_send_event', $plugin_admin, 'mailchimp_woocommerce_send_event' );

    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = MailChimp_WooCommerce_Public::instance();

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_footer', $plugin_public, 'add_inline_footer_script');

        $this->loader->add_action('woocommerce_after_checkout_form', $plugin_public, 'add_JS_checkout');
        $this->loader->add_action('woocommerce_register_form', $plugin_public, 'add_JS_checkout');

        // set my-account opt-in checkbox
        $this->loader->add_action('woocommerce_edit_account_form', $plugin_public, 'user_my_account_opt_in', 100);
        $this->loader->add_action('woocommerce_save_account_details', $plugin_public, 'user_my_account_opt_in_save', 1);

        // set order opt-in checkbox
//        $this->loader->add_filter('woocommerce_admin_billing_fields', $plugin_public, 'order_subscribe_user');
//        $this->loader->add_action('woocommerce_checkout_create_order', $plugin_public, 'save_order_subscribe_user', 220, 2);
//
//        // set order opt-in checkbox
//        $this->loader->add_action('woocommerce_admin_order_data_after_order_details', $plugin_public, 'order_subscribe_user');
//        $this->loader->add_action('woocommerce_process_shop_order_meta', $plugin_public, 'save_order_subscribe_user', 100, 1);
	}

	/**
	 * Handle the newsletter actions here.
	 */
	private function activateMailChimpNewsletter()
	{
		$service = MailChimp_Newsletter::instance();

		if ($this->is_configured && $service->isConfigured()) {

			$service->setEnvironment($this->environment);
			$service->setVersion($this->version);

			// adding the ability to render the checkbox on another screen of the checkout page.
			$render_on = $service->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form');

			$this->loader->add_action($render_on, $service, 'applyNewsletterField');

			$this->loader->add_action('woocommerce_ppe_checkout_order_review', $service, 'applyNewsletterField');
			$this->loader->add_action('woocommerce_register_form', $service, 'applyNewsletterField');

			$this->loader->add_action('woocommerce_checkout_order_processed', $service, 'processNewsletterField', 10, 2);
			$this->loader->add_action('woocommerce_ppe_do_payaction', $service, 'processPayPalNewsletterField');
			$this->loader->add_action('woocommerce_register_post', $service, 'processRegistrationForm', 10, 3);
		}
	}

	/**
	 * Handle all the service hooks here.
	 */
	private function activateMailChimpService()
	{
		$service = MailChimp_Service::instance();
		if ($service->isConfigured()) {

			$service->setEnvironment($this->environment);
			$service->setVersion($this->version);

			// core hook setup
			$this->loader->add_action('admin_init', $service, 'adminReady');
			$this->loader->add_action('woocommerce_init', $service, 'wooIsRunning');

			// for the data sync we need to configure basic auth.
			$this->loader->add_filter('http_request_args', $service, 'addHttpRequestArgs', 10, 2);

			// campaign tracking
			$this->loader->add_action( 'init', $service, 'handleCampaignTracking' );

			// order hooks
            $this->loader->add_action('woocommerce_order_status_changed', $service, 'handleOrderStatusChanged', 11, 3);

			// refunds
            $this->loader->add_action('woocommerce_order_partially_refunded', $service, 'onPartiallyRefunded', 20);
            
            // set user profile info
            $this->loader->add_action('show_user_profile', $service, 'user_subscribed_profile', 100);
            $this->loader->add_action('edit_user_profile', $service, 'user_subscribed_profile', 100);
            $this->loader->add_action('personal_options_update', $service, 'user_update_subscribe_status', 100);
            $this->loader->add_action('edit_user_profile_update', $service, 'user_update_subscribe_status', 100);
            // cart hooks
            $this->loader->add_filter('woocommerce_update_cart_action_cart_updated', $service, 'handleCartUpdated');
			$this->loader->add_action('woocommerce_cart_item_set_quantity', $service, 'handleCartUpdated');
			$this->loader->add_action('woocommerce_add_to_cart', $service, 'handleCartUpdated');
			$this->loader->add_action('woocommerce_cart_item_removed', $service, 'handleCartUpdated');

			// save post hooks
			$this->loader->add_action('woocommerce_new_order', $service, 'handleOrderCreate', 200, 2);
            $this->loader->add_action('woocommerce_update_order', $service, 'handleOrderUpdate', 10, 2);
            $this->loader->add_action('save_post_product', $service, 'handleProductCreated', 10, 3);
            $this->loader->add_action('woocommerce_before_delete_product_variation', $service, 'handleDeleteProductVariation');

			// this needs to listen for the title and the description updates.
            $this->loader->add_action('post_updated', $service, 'handleProductUpdated', 10, 3);

			// here's the hook we need to check for "relevant fields" where we can see which property was updated.
			$this->loader->add_action('woocommerce_product_object_updated_props', $service, 'handleProcessProductMeta', 10, 2);

			// we need to listen for all 3 events because changes aren't the same as "new" or "deleted".
			$this->loader->add_action('updated_post_meta', $service, 'handleProductMetaUpdated', 10, 4);
            $this->loader->add_action('added_post_meta', $service, 'handleProductMetaUpdated', 10, 4);
            $this->loader->add_action('deleted_post_meta', $service, 'handleProductMetaUpdated', 10, 4);

			// hooks for user meta updates and additions
			$this->loader->add_action('added_user_meta', $service, 'handleUserMetaUpdated', 10, 4);
			$this->loader->add_action('updated_user_meta', $service, 'handleUserMetaUpdated', 10, 4);

			$this->loader->add_action('wp_trash_post', $service, 'handlePostTrashed');
            $this->loader->add_action('untrashed_post', $service, 'handlePostRestored');
			//coupons
            $this->loader->add_action('woocommerce_new_coupon', $service, 'handleNewCoupon');
            $this->loader->add_action('woocommerce_coupon_options_save', $service, 'handleCouponSaved', 10, 2);
            $this->loader->add_action('woocommerce_api_create_coupon', $service, 'handleCouponSaved', 9, 2);

            $this->loader->add_action('woocommerce_delete_coupon', $service, 'handlePostTrashed');
            $this->loader->add_action('woocommerce_trash_coupon', $service, 'handlePostTrashed');
            
            $this->loader->add_action('woocommerce_rest_delete_shop_coupon_object', $service, 'handleAPICouponTrashed', 10, 3);

			// handle the user registration hook
			$this->loader->add_action('user_register', $service, 'handleUserRegistration');
			// handle the user updated profile hook
			$this->loader->add_action('profile_update', $service, 'handleUserUpdated', 100, 2);

			// get user by hash ( public and private )
            $this->loader->add_action('wp_ajax_mailchimp_get_user_by_hash', $service, 'get_user_by_hash');
            $this->loader->add_action('wp_ajax_nopriv_mailchimp_get_user_by_hash', $service, 'get_user_by_hash');

            // set user by email hash ( public and private )
            $this->loader->add_action('wp_ajax_mailchimp_set_user_by_email', $service, 'set_user_by_email');
            $this->loader->add_action('wp_ajax_nopriv_mailchimp_set_user_by_email', $service, 'set_user_by_email');



            $jobs_classes = array(
                "MailChimp_Woocommerce_Single_Customer",
                "MailChimp_WooCommerce_Single_Order",
                "MailChimp_WooCommerce_SingleCoupon",
                "MailChimp_WooCommerce_Single_Product",
                "MailChimp_WooCommerce_Single_Product_Variation",
                "MailChimp_WooCommerce_Cart_Update",
                "MailChimp_WooCommerce_User_Submit",
                "MailChimp_WooCommerce_Process_Customers",
                "MailChimp_WooCommerce_Process_Coupons",
                "MailChimp_WooCommerce_Process_Orders",
                "MailChimp_WooCommerce_Process_Products",
                "MailChimp_WooCommerce_WebHooks_Sync"
            );
            foreach ($jobs_classes as $job_class) {
                $this->loader->add_action($job_class, $service, 'mailchimp_process_single_job');
            }
            
            // sync stats manager
            $this->loader->add_action('MailChimp_WooCommerce_Process_Full_Sync_Manager', $service, 'mailchimp_process_sync_manager');
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.1
	 * @return    MailChimp_WooCommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}