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
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/includes
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
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/includes
 * @author     Ryan Hungate <ryan@mailchimp.com>
 */
class MailChimp_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      MailChimp_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $environment = 'production';

	protected static $logging_config = null;

	/**
	 * @return object
	 */
	public static function getLoggingConfig()
	{
		if (is_object(static::$logging_config)) {
			return static::$logging_config;
		}

		$plugin_options = get_option('mailchimp-woocommerce');
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

		return static::$logging_config = (object) array(
			'enable_logging' => (bool) $enable_logging,
			'account_id' => $account_id,
			'username' => $username,
			'endpoint' => 'https://ecommerce.'.$data_center.'.list-manage.com/ecommerce/log',
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
	public function __construct($environment = 'production', $version = '1.0.0') {

		$this->plugin_name = 'mailchimp-woocommerce';
		$this->version = $version;
		$this->environment = $environment;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->activateMailChimpNewsletter();
		$this->activateMailChimpService();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - MailChimp_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - MailChimp_Woocommerce_i18n. Defines internationalization functionality.
	 * - MailChimp_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - MailChimp_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		$path = plugin_dir_path( dirname( __FILE__ ) );

		/** The abstract options class.*/
		require_once $path . 'includes/class-mailchimp-woocommerce-options.php';

		/** The class responsible for orchestrating the actions and filters of the core plugin.*/
		require_once $path . 'includes/class-mailchimp-woocommerce-loader.php';

		/** The class responsible for defining internationalization functionality of the plugin. */
		require_once $path . 'includes/class-mailchimp-woocommerce-i18n.php';

		/** The service class.*/
		require_once $path . 'includes/class-mailchimp-woocommerce-service.php';

		/** The newsletter class. */
		require_once $path . 'includes/class-mailchimp-woocommerce-newsletter.php';

		/** The class responsible for defining all actions that occur in the admin area.*/
		require_once $path . 'admin/class-mailchimp-woocommerce-admin.php';

		/** The class responsible for defining all actions that occur in the public-facing side of the site. */
		require_once $path . 'public/class-mailchimp-woocommerce-public.php';

		/** Require all the MailChimp Assets for the API */
		require_once $path . 'includes/api/class-mailchimp-api.php';
		require_once $path . 'includes/api/class-mailchimp-woocommerce-api.php';
		require_once $path . 'includes/api/class-mailchimp-woocommerce-create-list-submission.php';
		require_once $path . 'includes/api/class-mailchimp-woocommerce-transform-products.php';
		require_once $path . 'includes/api/class-mailchimp-woocommerce-transform-orders.php';

		/** Require all the mailchimp api asset classes */
		require_once $path . 'includes/api/assets/class-mailchimp-address.php';
		require_once $path . 'includes/api/assets/class-mailchimp-cart.php';
		require_once $path . 'includes/api/assets/class-mailchimp-customer.php';
		require_once $path . 'includes/api/assets/class-mailchimp-line-item.php';
		require_once $path . 'includes/api/assets/class-mailchimp-order.php';
		require_once $path . 'includes/api/assets/class-mailchimp-product.php';
		require_once $path . 'includes/api/assets/class-mailchimp-product-variation.php';
		require_once $path . 'includes/api/assets/class-mailchimp-store.php';

		/** Require all the api error helpers */
		require_once $path . 'includes/api/errors/class-mailchimp-error.php';
		require_once $path . 'includes/api/errors/class-mailchimp-server-error.php';

		/** Require the various helper scripts */
		require_once $path . 'includes/api/helpers/class-mailchimp-woocommerce-api-currency-codes.php';
		require_once $path . 'includes/api/helpers/class-mailchimp-woocommerce-api-locales.php';

		/** Background job sync tools */

		// make sure the queue exists first since the other files depend on it.
		require_once $path . 'includes/vendor/queue.php';

		// the abstract bulk sync class
		require_once $path.'includes/processes/class-mailchimp-woocommerce-abstract-sync.php';

		// bulk data sync
		require_once $path.'includes/processes/class-mailchimp-woocommerce-process-orders.php';
		require_once $path.'includes/processes/class-mailchimp-woocommerce-process-products.php';

		// individual item sync
		require_once $path.'includes/processes/class-mailchimp-woocommerce-cart-update.php';
		require_once $path.'includes/processes/class-mailchimp-woocommerce-single-order.php';
		require_once $path.'includes/processes/class-mailchimp-woocommerce-single-product.php';
		require_once $path.'includes/processes/class-mailchimp-woocommerce-user-submit.php';

		// fire up the loader
		$this->loader = new MailChimp_Woocommerce_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the MailChimp_Woocommerce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new MailChimp_Woocommerce_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new MailChimp_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Add menu item
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

		// Add Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php');
		$this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

		// make sure we're listening for the admin init
		$this->loader->add_action('admin_init', $plugin_admin, 'options_update');

		// put the menu on the admin top bar.
		//$this->loader->add_action('admin_bar_menu', $plugin_admin, 'admin_bar', 100);

		$this->loader->add_action('plugins_loaded', $plugin_admin, 'update_db_check');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new MailChimp_Woocommerce_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Handle the newsletter actions here.
	 */
	private function activateMailChimpNewsletter()
	{
		$service = new MailChimp_Newsletter();

		if ($service->isConfigured()) {

			$service->setEnvironment($this->environment);
			$service->setVersion($this->version);

			// adding the ability to render the checkbox on another screen of the checkout page.
			$render_on = $service->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form');
			$this->loader->add_action($render_on, $service, 'applyNewsletterField', 5);

			$this->loader->add_action('woocommerce_ppe_checkout_order_review', $service, 'applyNewsletterField', 5);
			$this->loader->add_action('woocommerce_register_form', $service, 'applyNewsletterField', 5);

			$this->loader->add_action('woocommerce_checkout_order_processed', $service, 'processNewsletterField', 5, 2);
			$this->loader->add_action('woocommerce_ppe_do_payaction', $service, 'processPayPalNewsletterField', 5, 1);
			$this->loader->add_action('woocommerce_register_post', $service, 'processRegistrationForm', 5, 3);
		}
	}

	/**
	 * Handle all the service hooks here.
	 */
	private function activateMailChimpService()
	{
		$service = new MailChimp_Service();

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
            $this->loader->add_action('woocommerce_thankyou', $service, 'onNewOrder', 1);
			$this->loader->add_action('woocommerce_api_create_order', $service, 'handleOrderStatusChanged', 10);
			$this->loader->add_action('woocommerce_order_status_changed', $service, 'handleOrderStatusChanged', 2);

			// cart hooks
			$this->loader->add_action('woocommerce_cart_updated', $service, 'handleCartUpdated');
			$this->loader->add_action('woocommerce_add_to_cart', $service, 'handleCartUpdated');
			$this->loader->add_action('woocommerce_cart_item_removed', $service, 'handleCartUpdated');

			// save post hook for products
			$this->loader->add_action('save_post', $service, 'handlePostSaved', 10, 3);

			// handle the user registration hook
			$this->loader->add_action('user_register', $service, 'handleUserRegistration');
			// handle the user updated profile hook
			$this->loader->add_action('profile_update', $service, 'handleUserUpdated', 10, 2);

			// when someone deletes a user??
			//$this->loader->add_action('delete_user', $service, 'handleUserDeleting');

			$this->loader->add_action('wp_ajax_nopriv_mailchimp_get_user_by_hash', $service, 'get_user_by_hash');
			$this->loader->add_action('wp_ajax_nopriv_mailchimp_set_user_by_email', $service, 'set_user_by_email');
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
	 * @return    MailChimp_Woocommerce_Loader    Orchestrates the hooks of the plugin.
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
