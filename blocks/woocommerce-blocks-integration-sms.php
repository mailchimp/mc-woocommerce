<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Mailchimp_Woocommerce_SMS_Blocks_Integration
 *
 * Class for integrating SMS marketing consent block with WooCommerce Checkout
 */
class Mailchimp_Woocommerce_SMS_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'mailchimp-sms';
	}

	/**
	 * @throws Exception
	 */
	public function initialize() {
		$this->register_frontend_scripts();
		$this->register_editor_scripts();
		$this->register_editor_blocks();
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_block', [ $this, 'add_attributes_to_frontend_blocks' ], 10, 1 );
	}

	/**
	 * Register frontend scripts for SMS block
	 * 
	 * @return bool
	 */
	public function register_frontend_scripts() {
		$script_path       = '/build/sms-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/sms-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		$result = wp_register_script(
			'mailchimp-sms-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( ! $result ) {
			return false;
		}

		wp_set_script_translations(
			'mailchimp-sms-frontend',
			'mailchimp-woocommerce',
			dirname( dirname( __FILE__ ) ) . '/languages'
		);

		return true;
	}

	/**
	 * Register editor scripts for SMS block
	 */
	public function register_editor_scripts() {
		$script_path       = '/build/sms-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/sms-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'mailchimp-sms-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'mailchimp-sms-editor',
			'mailchimp-woocommerce',
			dirname( dirname( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'mailchimp-sms-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'mailchimp-sms-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data = array(
			'smsDefaultText'      => $this->getSmsLabelText(),
			'smsDisclaimerText'   => $this->getSmsDisclaimerText(),
			'smsStatus'           => $this->getSmsOptinStatus(),
			'userSmsSubscribed'   => $this->isUserSmsSubscribed(),
			'smsEnabled'          => $this->isSmsEnabled() && $this->merchantHasSmsApproved(),
			'audienceName'        => $this->getAudienceName(),
			'smsSendingCountries' => $this->getSmsSendingCountries(),
		);

		return $data;
	}

	/**
	 * Register SMS block.
	 */
	public function register_editor_blocks() {
		register_block_type( dirname( __FILE__ ) . '/assets/js/checkout-sms-subscription-block', array(
			'editor_script' => 'mailchimp-sms-editor',
		));
	}

	/**
	 * This allows dynamic (JS) blocks to access attributes in the frontend.
	 *
	 * @param array $allowed_blocks
	 * @return array
	 */
	public function add_attributes_to_frontend_blocks( $allowed_blocks ) {
		if ( ! is_array( $allowed_blocks ) ) {
			$allowed_blocks = (array) $allowed_blocks;
		}
		$allowed_blocks[] = 'woocommerce/mailchimp-sms-subscription';
		return $allowed_blocks;
	}

	/**
	 * Process SMS consent from block checkout
	 *
	 * @param WC_Order $order
	 * @param array $request
	 */
	public static function order_processed( $order, $request ) {
		// Get SMS data from the request extensions
		$sms_optin = isset( $request['extensions']['mailchimp-sms']['smsOptin'] ) 
			? (bool) $request['extensions']['mailchimp-sms']['smsOptin'] 
			: false;
		$sms_phone = isset( $request['extensions']['mailchimp-sms']['smsPhone'] ) 
			? sanitize_text_field( $request['extensions']['mailchimp-sms']['smsPhone'] ) 
			: '';

		// Only store if they opted in and provided a phone number
		if ( $sms_optin && ! empty( $sms_phone ) ) {
			// Update order meta
			MailChimp_WooCommerce_HPOS::update_order_meta( $order->get_id(), 'mailchimp_woocommerce_sms_subscribed', true );
			MailChimp_WooCommerce_HPOS::update_order_meta( $order->get_id(), 'mailchimp_woocommerce_sms_phone', $sms_phone );

			// Update user meta if logged in
			if ( $user_id = $order->get_user_id() ) {
				update_user_meta( $user_id, 'mailchimp_woocommerce_sms_subscribed', true );
				update_user_meta( $user_id, 'mailchimp_woocommerce_sms_phone', $sms_phone );
			}
		}
	}

	/**
	 * Process SMS consent for customer after order
	 *
	 * @param WC_Order $order
	 */
	public static function order_customer_processed( $order ) {
		$wc_order = wc_get_order( $order->get_id() );
		$sms_subscribed = $wc_order->get_meta( 'mailchimp_woocommerce_sms_subscribed' );
		$sms_phone = $wc_order->get_meta( 'mailchimp_woocommerce_sms_phone' );

		if ( $user_id = $wc_order->get_user_id() ) {
			if ( $sms_subscribed ) {
				update_user_meta( $user_id, 'mailchimp_woocommerce_sms_subscribed', true );
				if ( ! empty( $sms_phone ) ) {
					update_user_meta( $user_id, 'mailchimp_woocommerce_sms_phone', $sms_phone );
				}
			}
		}
	}

	/**
	 * Get SMS checkbox label text (fixed per compliance)
	 *
	 * @return string
	 */
	protected function getSmsLabelText() {
		// Compliance: label text cannot be customized
		return __( 'Text me with news and offers', 'mailchimp-for-woocommerce' );
	}

	/**
	 * Get SMS disclaimer text (fixed per compliance)
	 *
	 * @return string
	 */
	protected function getSmsDisclaimerText() {
		// Compliance: disclaimer text cannot be customized
		$audience_name = $this->getAudienceName();
		$prefix = ! empty( $audience_name ) ? $audience_name . ' – ' : '';
        $in_sentence = ! empty( $audience_name ) ? $audience_name : 'us';
		return $prefix . __( 'By providing your phone number, you agree to receive promotional and marketing messages (e.g. abandoned carts), notifications, and customer service communications from '.$in_sentence.'. Message and data rates map apply. Consent is not a condition of purchase. Message frequency varies. Text HELP for help. Text STOP to cancel. See Terms and Privacy Policy.', 'mailchimp-for-woocommerce');
	}

	/**
	 * Get SMS opt-in default status (always unchecked per compliance)
	 *
	 * @return string 'uncheck' or 'hide'
	 */
	protected function getSmsOptinStatus() {
		// Compliance: checkbox cannot be pre-selected, always unchecked by default
		
		// If logged in and already subscribed, hide the checkbox
		if ( is_user_logged_in() ) {
			$user_status = get_user_meta( get_current_user_id(), 'mailchimp_woocommerce_sms_subscribed', true );
			if ( $user_status === true || $user_status === '1' ) {
				return 'hide';
			}
		}

		return 'uncheck';
	}

	/**
	 * Check if current user is already SMS subscribed
	 *
	 * @return bool
	 */
	protected function isUserSmsSubscribed() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$status = get_user_meta( get_current_user_id(), 'mailchimp_woocommerce_sms_subscribed', true );
		return $status === true || $status === '1';
	}

	/**
	 * Check if SMS is enabled for this store
	 *
	 * @return bool
	 */
	protected function isSmsEnabled() {
		$options = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce' );
		return isset( $options['mailchimp_sms_enabled'] ) && (bool) $options['mailchimp_sms_enabled'];
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
	 * Get SMS sending countries for the merchant
	 *
	 * @return array
	 */
	protected function getSmsSendingCountries() {
		try {
			if ( ! mailchimp_is_configured() ) {
				return array();
			}
			$list_id = mailchimp_get_list_id();
			if ( ! $list_id ) {
				return array();
			}
			$api = mailchimp_get_api();
			$sms_status = $api->getCachedSmsApplicationStatus( $list_id );
			if ( $sms_status && ! empty( $sms_status['sending_countries'] ) ) {
				return $sms_status['sending_countries'];
			}
			return array();
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Get the audience name for disclaimer
	 *
	 * @return string
	 */
	protected function getAudienceName() {
		try {
			if ( ! mailchimp_is_configured() ) {
				return '';
			}
			$list_id = mailchimp_get_list_id();
			if ( ! $list_id ) {
				return '';
			}
			$api = mailchimp_get_api();
			$list = $api->getList( $list_id );
			return isset( $list['name'] ) ? $list['name'] : '';
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return MAILCHIMP_WOOCOMMERCE_NEWSLETTER_VERSION;
	}
}
