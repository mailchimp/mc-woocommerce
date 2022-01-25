<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
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
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize()
    {
		$this->register_frontend_scripts();
        $this->register_editor_scripts();
		$this->register_editor_blocks();
		$this->extend_store_api();
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_block', [ $this, 'add_attributes_to_frontend_blocks' ], 10, 1 );
        add_action('woocommerce_before_order_object_save', [$this, 'capture_from_store_api'], 1);
        add_action('woocommerce_blocks_checkout_update_order_from_request', [$this, 'order_processed'], 10, 2);
        //add_action('woocommerce_blocks_checkout_order_processed', [$this, 'order_processed'], 10, 2);
	}

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
	 * @param string[] $allowed_blocks
	 */
	public function add_attributes_to_frontend_blocks( $allowed_blocks )
    {
		$allowed_blocks[] = 'woocommerce/mailchimp-newsletter-subscription';
		return $allowed_blocks;
	}

	/**
	 * Add schema Store API to support posted data.
	 */
	public function extend_store_api()
    {
        /** @var ExtendRestApi $extend */
		$extend = Package::container()->get(ExtendRestApi::class);

		$extend->register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => $this->get_name(),
				'schema_callback' => function() {
					return array(
						'optin' => array(
							'description' => __( 'Subscribe to marketing opt-in.', 'mailchimp-newsletter' ),
							'type'        => 'boolean',
							'context'     => array(),
							'arg_options' => array(
								'validate_callback' => function( $value ) {
									if ( ! is_bool( $value ) ) {
										return new \WP_Error( 'api-error', 'value of type ' . gettype( $value ) . ' was posted to the newsletter optin callback' );
									}
									return true;
								},
							),
						),
					);
				},
			)
		);
	}

    /**
     * Store guest info when they submit email from Store API.
     *
     * The guest email, first name and last name are captured.
     *
     * @see \Automattic\WooCommerce\Blocks\StoreApi\Routes\CartUpdateCustomer
     *
     * @param \WC_Order $order
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
     * @param \WC_Order $order
     * @param $request
     */
    public function order_processed($order, $request)
    {
        $meta_key = 'mailchimp_woocommerce_is_subscribed';
        $optin = $request['extensions']['mailchimp-newsletter']['optin'];
        //$email = $request['billing_address']['email'];

        // update the order meta for the subscription status to support legacy functions
        update_post_meta($order->get_id(), $meta_key, $optin);

        // if the user id exists
        if (($user_id = $order->get_user_id())) {
            // update the user subscription meta
            update_user_meta($user_id, $meta_key, $optin);
            // submit this if there's a proper user ID and is a subscriber.
            if ((bool) $optin) {
                // probably need to add the GDPR fields and language in to this submission next.
                $language = null;
                $gdpr_fields = null;
                mailchimp_handle_or_queue(
                    new MailChimp_WooCommerce_User_Submit(
                        $user_id,
                        true,
                        null,
                        $language,
                        $gdpr_fields
                    )
                );
            }
        }

        // maybe add the filter to only submit orders from subscribers?
        $service = MailChimp_Service::instance();
        $tracking = $service->onNewOrder($order->get_id());
        $service->onOrderSave($order->get_id(), $tracking, true);
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
        return mailchimp_get_api()->getCachedGDPRFields($list_id);
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
