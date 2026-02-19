<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;
/**
 * Class Mailchimp_Woocommerce_Newsletter_Blocks_Integration
 *
 * Class for integrating marketing optin block with WooCommerce Checkout
 *
 */
class Mailchimp_Woocommerce_Sms_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name()
    {
		return 'mailchimp-sms-consent';
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
		$script_path       = '/build/sms-consent-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/sms-consent-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		$result = wp_register_script(
			'mailchimp-sms-consent-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if (!$result) {
		    return false;
        }

		wp_set_script_translations(
			'mailchimp-sms-consent-frontend', // script handle
			'mailchimp-woocommerce', // text domain
			dirname(dirname( __FILE__ )) . '/languages'
		);
		return true;
	}

    public function register_editor_scripts()
    {
        $script_path       = '/build/sms-consent-block.js';
        $script_url        = plugins_url( $script_path, __FILE__ );
        $script_asset_path = dirname( __FILE__ ) . '/build/sms-consent-block.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $script_asset_path ),
            );

        wp_register_script(
            'mailchimp-sms-consent-editor',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_set_script_translations(
            'mailchimp-sms-consent-editor', // script handle
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
		    'mailchimp-sms-consent-frontend'
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
            'mailchimp-sms-consent-editor'
        );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data()
    {
        $active = Mailchimp_Sms_Consent::isEligibleCountry() && MailChimp_Sms_Consent::isSmsProgramActive();

        $data = array(
            'optinDefaultText' => __( 'Text me with news and offers', 'mailchimp-sms-consent' ),
        );

        $data['gdprStatus'] = $active ? $this->getOptinStatus() : 'hide';

        if (is_user_logged_in()) {
            $subscribed = is_user_logged_in() && get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_consent_subscribed', true);
        } else {
            $subscribed = false;
        }
//        $checkbox_settings = array(
//            [ 'label' => esc_html__( 'Checked by default', 'mailchimp-for-woocommerce' ), 'value' => 'check' ],
//            [ 'label' => esc_html__( 'Unchecked by default', 'mailchimp-for-woocommerce' ), 'value' => 'uncheck' ],
//        );
        $checkbox_settings = array();

        $data['userSmsSubscribed'] = $subscribed === true || $subscribed === '1';
        $data['smsEnabled'] = $this->isSmsEnabled();
        $data['defaultDisclaimer'] = $this->getSmsDisclaimerText();
        $data['audienceName'] = $this->getAudienceName();
        $data['smsSendingCountries'] = $this->getSmsSendingCountries();
        $data['checkboxSettings'] = apply_filters('mailchimp_checkout_sms_consent_options', $checkbox_settings);;

        if (defined('MAILCHIMP_DEBUG') && MAILCHIMP_DEBUG === true) {
            mailchimp_debug('eligible_countries', 'test', [
                'data' => $data['smsSendingCountries']
            ]);
        }

		return $data;
	}

	/**
	 * Register blocks.
	 */
	public function register_editor_blocks()
    {
        register_block_type(dirname(__FILE__) . '/assets/js/checkout-sms-consent-block', array(
            'editor_script' => 'mailchimp-sms-consent-editor',
        ));
    }

	/**
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
		$allowed_blocks[] = 'woocommerce/mailchimp-sms-consent-subscription';
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
        $meta_key = 'mailchimp_woocommerce_sms_consent_subscribed';

        mailchimp_debug('order_processed', 'hook with extensions', [
            'order' => $order->get_id(),
            'request' => $request['extensions']
        ]);

        $optin = $request['extensions']['mailchimp-sms-consent']['smsOptin'];
        $phone = $request['extensions']['mailchimp-sms-consent']['smsPhone'];
        // update the order meta for the subscription status to support legacy functions

        MailChimp_WooCommerce_HPOS::update_order_meta($order->get_id(), $meta_key, $optin);

        $tracking = MailChimp_Service::instance()->onNewOrder($order->get_id());
        // queue up the single order to be processed.
        $landing_site = isset($tracking) && isset($tracking['landing_site']) ? $tracking['landing_site'] : null;
        $language = substr( get_locale(), 0, 2 );

        // update the post meta with campaign tracking details for future sync
        if (!empty($landing_site)) {
            MailChimp_WooCommerce_HPOS::update_order_meta($order->get_id(), 'mailchimp_woocommerce_landing_site', $landing_site);
        }

        // todo here was gdpr fields as lat parameter
        $handler = new MailChimp_WooCommerce_Single_Order($order->get_id(), null, $landing_site, $language, []);
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
        $meta_key   = 'mailchimp_woocommerce_sms_consent_subscribed';
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

    protected function isSmsEnabled() {
        $options = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce' );
        return isset( $options['mailchimp_sms_consent_enabled'] ) && (bool) $options['mailchimp_sms_consent_enabled'];
    }

    /**
     * Check if merchant has approved SMS application
     *
     * @return bool
     */
    protected function merchantHasSmsApproved() {
        try {
            if ( ! mailchimp_is_configured() ) {
                return false;
            }
            $list_id = mailchimp_get_list_id();
            if ( ! $list_id ) {
                return false;
            }
            $api = mailchimp_get_api();
            $sms_status = $api->getCachedSmsApplicationStatus( $list_id );

            return $sms_status && ! empty( $sms_status['enabled'] );
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function getOptinStatus()
    {
        $mailchimp_newsletter = new MailChimp_Newsletter();
        // if the user has chosen to hide the checkbox, don't do anything.
        if ( ( $default_setting = $mailchimp_newsletter->getOption('mailchimp_checkbox_defaults_sms', 'uncheck') ) === 'hide') {
            return 'hide';
        }

        // if the user chose 'check' or nothing at all, we default to true.
        $default_checked = $default_setting === 'check';
        $status = $default_checked;

        // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
        // otherwise we use the default settings.
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_consent_subscribed', true);
            /// if the user is logged in - and is already subscribed - just ignore this checkbox.
            if ($status === '' || $status === null) {
                $status = $default_checked;
            }
        }

        return $status === true || $status === '1' ? 'check' : 'uncheck';
    }

    protected function getSmsDisclaimerText() {
        // Compliance: disclaimer text cannot be customized
        $audience_name = $this->getAudienceName();
        $prefix = ! empty( $audience_name ) ? $audience_name . ' – ' : '';
        $in_sentence = ! empty( $audience_name ) ? $audience_name : 'us';
        return $prefix . __( 'By providing your phone number, you agree to receive promotional and marketing messages (e.g. abandoned carts), notifications, and customer service communications from '.$in_sentence.'. Message and data rates map apply. Consent is not a condition of purchase. Message frequency varies. Text HELP for help. Text STOP to cancel. See Terms and Privacy Policy.', 'mailchimp-for-woocommerce');
    }

    protected function getAudienceName() {
        try {
            if ( ! mailchimp_is_configured() ) {
                return '';
            }
            $list_id = mailchimp_get_list_id();
            if ( ! $list_id ) {
                return '';
            }
            $list_name = MailChimp_WooCommerce_Admin::instance()->getListName();
            return !empty($list_name) ? $list_name : '';
        } catch ( Exception $e ) {
            return '';
        }
    }

    protected function getSmsSendingCountries() {
        return MailChimp_Sms_Consent::$allowedCountries;
    }

    public function getSmsProgram()
    {
        try {
            if ( ! mailchimp_is_configured() ) {
                return array();
            }
            $list_id = mailchimp_get_list_id();
            if ( ! $list_id ) {
                return array();
            }
            $api = mailchimp_get_api();
            return $api->getCachedSmsProgram( $list_id );
        } catch ( Exception $e ) {
            return array();
        }
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
