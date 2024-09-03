<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;
/**
 * Class Mailchimp_Woocommerce_Newsletter_Blocks_Integration
 *
 * Class for integrating marketing optin block with WooCommerce Checkout
 *
 */
class Mailchimp_Woocommerce_Newsletter_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name()
    {
		return 'mailchimp-newsletter';
	}

	/**
	 * @throws Exception
	 */
	public function initialize()
    {
		$this->register_frontend_scripts();
        $this->register_editor_scripts();
		$this->register_editor_blocks();
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_block', [ $this, 'add_attributes_to_frontend_blocks' ], 10, 1 );
        add_action('woocommerce_before_order_object_save', [$this, 'capture_from_store_api'], 1);
	}

	/**
	 * @return bool
	 */
	public function register_frontend_scripts()
    {
		$script_path       = '/build/newsletter-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/newsletter-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		$result = wp_register_script(
			'mailchimp-newsletter-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if (!$result) {
		    return false;
        }

		wp_set_script_translations(
			'mailchimp-newsletter-frontend', // script handle
			'mailchimp-woocommerce', // text domain
			dirname(dirname( __FILE__ )) . '/languages'
		);
		return true;
	}

    public function register_editor_scripts()
    {
        $script_path       = '/build/newsletter-block.js';
        $script_url        = plugins_url( $script_path, __FILE__ );
        $script_asset_path = dirname( __FILE__ ) . '/build/newsletter-block.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $script_asset_path ),
            );

        wp_register_script(
            'mailchimp-newsletter-editor',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_set_script_translations(
            'mailchimp-newsletter-editor', // script handle
            'mailchimp-woocommerce', // text domain
            dirname(dirname( __FILE__ )) . '/languages'
        );
    }

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles()
    {
		return array(
		    'mailchimp-newsletter-frontend'
        );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles()
    {
		return array(
            'mailchimp-newsletter-editor'
        );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data()
    {
        $gdpr = $this->getGdprFields();
        $data = array(
            'optinDefaultText' => __( 'I want to receive updates about products and promotions.', 'mailchimp-newsletter' ),
        );
        $data['gdprStatus'] = $this->getOptinStatus();

        if (is_user_logged_in()) {
            $subscribed = is_user_logged_in() && get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed', true);
        } else {
            $subscribed = false;
        }

        $data['userSubscribed'] = $subscribed === true || $subscribed === '1';

        $checkbox_settings = array(
            [ 'label' => esc_html__( 'Checked by default', 'mailchimp-for-woocommerce' ), 'value' => 'check' ],
            [ 'label' => esc_html__( 'Unchecked by default', 'mailchimp-for-woocommerce' ), 'value' => 'uncheck' ],
        );

        $data['checkboxSettings'] = apply_filters('mailchimp_checkout_opt_in_options', $checkbox_settings);;

        if (!empty($gdpr)) {
            $data['gdprHeadline'] = __( 'Please select all the ways you would like to hear from us', 'mailchimp-newsletter' );
            $data['gdprFields'] = $gdpr;
        }

		return $data;
	}

	/**
	 * Register blocks.
	 */
	public function register_editor_blocks()
    {
        register_block_type( dirname( __FILE__ ) . '/assets/js/checkout-newsletter-subscription-block', array(
            'editor_script' => 'mailchimp-newsletter-editor',
        ));
	}

	/**
	 * This allows dynamic (JS) blocks to access attributes in the frontend.
	 *
	 * @param $allowed_blocks
	 *
	 * @return mixed
	 */
	public function add_attributes_to_frontend_blocks( $allowed_blocks )
    {
    	if (!is_array($allowed_blocks)) {
    		$allowed_blocks = (array) $allowed_blocks;
	    }
		$allowed_blocks[] = 'woocommerce/mailchimp-newsletter-subscription';
		return $allowed_blocks;
	}

	/**
	 * Store guest info when they submit email from Store API.
	 *
	 * The guest email, first name and last name are captured.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\CartUpdateCustomer
	 *
	 * @param WC_Order|WC_Order_Refund $order
	 *
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
    public function capture_from_store_api($order)
    {
        if ($order->get_status() !== 'checkout-draft' ||
            !$order->is_created_via( 'store-api' ) ||
            !$order->get_billing_email()) {
            return;
        }

        if (!($api = $this->api())) {
            return;
        }

        // this should allow us to do the same thing as previous without the javascript hook
        $service = MailChimp_Service::instance();
        $service->set_user_from_block_checkout($order->get_billing_email());
        $service->handleCartUpdated();
    }

    /**
     * @param WC_Order $order
     * @param $request
     */
    public static function order_processed($order, $request)
    {
        $meta_key = 'mailchimp_woocommerce_is_subscribed';
        $optin = $request['extensions']['mailchimp-newsletter']['optin'];
        $gdpr_fields = isset($request['extensions']['mailchimp-newsletter']['gdprFields']) ?
            (array) $request['extensions']['mailchimp-newsletter']['gdprFields'] : null;
        // update the order meta for the subscription status to support legacy functions

        MailChimp_WooCommerce_HPOS::update_order_meta($order->get_id(), $meta_key, $optin);
        /*update_post_meta($order->get_id(), $meta_key, $optin);*/
        // let's set the GDPR fields here just in case we need to pull them again.
        if (!empty($gdpr_fields)) {
            MailChimp_WooCommerce_HPOS::update_order_meta($order->get_id(), 'mailchimp_woocommerce_gdpr_fields', $gdpr_fields);
            //update_post_meta($order->get_id(), "mailchimp_woocommerce_gdpr_fields", $gdpr_fields);
        }

        $tracking = MailChimp_Service::instance()->onNewOrder($order->get_id());
        // queue up the single order to be processed.
        $landing_site = isset($tracking) && isset($tracking['landing_site']) ? $tracking['landing_site'] : null;
        $language = substr( get_locale(), 0, 2 );

        // update the post meta with campaign tracking details for future sync
        if (!empty($landing_site)) {
            MailChimp_WooCommerce_HPOS::update_order_meta($order->get_id(), 'mailchimp_woocommerce_landing_site', $landing_site);
        }

        $handler = new MailChimp_WooCommerce_Single_Order($order->get_id(), null, $landing_site, $language, $gdpr_fields);
        $handler->is_update = false;
        $handler->is_admin_save = is_admin();

        mailchimp_handle_or_queue($handler, 15);
    }

    /**
     * @param WC_Order $order
     */
    public static function order_customer_processed( $order )
    {
        // extract a new order object to take the relevant meta fields
        $wc_order   = wc_get_order( $order->get_id() );
        $meta_key   = 'mailchimp_woocommerce_is_subscribed';
        $optin      = $wc_order->get_meta( $meta_key );
        $gdpr_fields = $wc_order->get_meta( 'mailchimp_woocommerce_gdpr_fields' );

        // if the user id exists
        if ( ( $user_id = $wc_order->get_user_id() ) ) {
            // update the user subscription meta
            update_user_meta( $user_id, $meta_key, $optin );
            // submit this if there's a proper user ID and is a subscriber.
            if ( (bool) $optin ) {
                // probably need to add the GDPR fields and language in to this submission next.
				$language = get_user_meta($user_id, 'locale', true);
				if (strpos($language, '_') !== false) {
					$languageArray = explode('_', $language);
					$language = $languageArray[0];
				}

				mailchimp_handle_or_queue(
                    new MailChimp_WooCommerce_User_Submit(
                        $user_id,
                        '1',
                        null,
                        $language,
                        $gdpr_fields
                    )
                );
            }
        }
    }

    /**
     * @return bool|MailChimp_WooCommerce_MailChimpApi
     */
    protected function api()
    {
        if (!mailchimp_is_configured()) {
            return false;
        }
        return mailchimp_get_api();
    }

    /**
     * @return bool
     */
    protected function getOptinStatus()
    {
        $mailchimp_newsletter = new MailChimp_Newsletter();
        // if the user has chosen to hide the checkbox, don't do anything.
        if ( ( $default_setting = $mailchimp_newsletter->getOption('mailchimp_checkbox_defaults', 'check') ) === 'hide') {
            return 'hide';
        }

        // if the user chose 'check' or nothing at all, we default to true.
        $default_checked = $default_setting === 'check';
        $status = $default_checked;

        // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
        // otherwise we use the default settings.
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed', true);
            /// if the user is logged in - and is already subscribed - just ignore this checkbox.
            if ($status === '' || $status === null) {
                $status = $default_checked;
            }
        }

        return $status === true || $status === '1' ? 'check' : 'uncheck';
    }

    /**
     * @return array
     */
	protected function getGdprFields()
    {
        if (!mailchimp_is_configured()) {
            return array();
        }
        if (!($list_id = mailchimp_get_list_id())) {
            return array();
        }
        $fields = mailchimp_get_api()->getCachedGDPRFields($list_id);
        return is_array($fields) ? $fields : array();
    }

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file )
    {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return MAILCHIMP_WOOCOMMERCE_NEWSLETTER_VERSION;
	}
}
