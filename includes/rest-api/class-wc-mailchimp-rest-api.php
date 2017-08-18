<?php
/**
 * MailChimp main API class
 */
class WC_Mailchimp_Rest_API
 {

	/**
	 * Minimum version needed to run this version of the API.
	 */
	const WC_MIN_VERSION = '3.0.0';

	/**
	 * Class Instance.
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'check_dependencies' ) );
	}

	/**
	 * Loads API includes and registers routes.
	 */
	function init() {
		$this->includes();
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );
	}

	/**
	 * Makes sure WooCommerce is installed and up to date.
	 */
	public function check_dependencies() {
		if ( ! class_exists( 'woocommerce' ) || version_compare(
			get_option( 'woocommerce_db_version' ),
			WC_API_Dev::WC_MIN_VERSION,
			'<='
		) ) {
			add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
		}
	}

	/**
	 * Displays an admin notice if WooCommerce is not enabled or not the correct version.
	 */
	public function dependency_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="error"><p><strong>' . __( 'The WooCommerce plugin is inactive.' ) . '</strong> ' . sprintf( __( 'The WooCommerce plugin must be active and least version %s for the WooCommerce API Dev plugin to work. %sPlease install and activate WooCommerce%s.' ), WC_API_Dev::WC_MIN_VERSION, '<a href="' .esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' ) . '</p></div>';
		}
	}

	/**
	 * REST API includes.
	 * New endpoints/controllers can be added here.
	 *
	 * Controllers for the feature plugin are prefixed with WC_REST_DEV (rather than WC_REST)
	 * so that this plugin can play nice with the WooCommerce Core classes.
	 * They would be renamed on future sync to WooCommerce.
	 */
	public function includes() {
		include_once( dirname( __FILE__ ) . '/class-wc-mc-store-settings.php' );
	}

	/**
	 * Register REST API routes.
	 *
	 * New endpoints/controllers can be added here.
	 */
	public function register_routes() {
		$controllers = array(
			'WC_REST_MC_Store_Settings_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Class instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WC_Mailchimp_Rest_API::instance();
