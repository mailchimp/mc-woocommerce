<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/public
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class MailChimp_WooCommerce_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp-woocommerce-public.min.js', array(), $this->version, false);
		wp_localize_script($this->plugin_name, 'mailchimp_public_data', array(
			'site_url' => site_url(),
			'ajax_url' => admin_url('admin-ajax.php'),
		));

        // Enqueued script with localized data.
        wp_enqueue_script($this->plugin_name, '', array(), $this->version, true);

        // if we have the "fragment" we can just inject this vs. loading the file
        // otherwise, if we have the connected_site script url saved, we need to inject it and load from the CDN.
        //if (($site = mailchimp_get_connected_site_script_url()) && !empty($site)) {
        //   wp_enqueue_script($this->plugin_name.'_connected_site', $site, array(), $this->version, true);
        //}
	}

    public function add_inline_footer_script(){
        if (($fragment = mailchimp_get_connected_site_script_fragment()) && !empty($fragment)) {
            echo $fragment;
        }
    }
}
