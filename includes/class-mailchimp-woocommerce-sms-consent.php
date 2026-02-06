<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/22/16
 * Time: 9:09 AM
 */

class MailChimp_Sms_Consent extends MailChimp_WooCommerce_Options
{
    /** @var null|static */
    protected static $_instance = null;

    public static $allowedCountries = ['AU', 'AT', 'CA', 'FR', 'DE', 'IE', 'IT', 'ES', 'CH', 'NL', 'US', 'GB'];

    /**
     * @return MailChimp_Sms_Consent
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) {
            return static::$_instance;
        }
        $env = mailchimp_environment_variables();
        static::$_instance = new MailChimp_Sms_Consent();
        static::$_instance->setVersion($env->version);
        return static::$_instance;
    }

	/**
	 * @param $checkout
	 */
    public function applyField($checkout)
    {
        // some folks have asked to be able to check out on behalf of customers. I guess this makes sense
        // if they want to do this, but it needs to be a constant and custom.
        $allow_admin = defined('MAILCHIMP_ALLOW_ADMIN_NEWSLETTER') && MAILCHIMP_ALLOW_ADMIN_NEWSLETTER;

        if ($allow_admin || !is_admin()) {
            if (($default_setting = $this->getOption('mailchimp_sms_consent_checkbox_action', 'check')) === 'hide') {
                return;
            }

            $label = __('Text me with news and offer', 'mailchimp-for-woocommerce');

            $default_checked = $default_setting === 'check';
            $status = $default_checked;
            $hide_optin_for_subscriber = false;

            // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
            // otherwise we use the default settings.
            if (is_user_logged_in()) {
                $status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_consent_subscribed', true);
                $hide_optin_for_subscriber = $status === true || $status === '1';

                /// if the user is logged in - and is already subscribed - just ignore this checkbox.
                if ($status === '' || $status === null) {
                    $status = $default_checked;
                }
            }


            // echo out the subscription checkbox.
            $checkbox = '<p class="form-row form-row-wide mailchimp-newsletter">';
            $checkbox .= '<label for="mailchimp_woocommerce_sms_consent" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline">';
            $checkbox .= '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="mailchimp_woocommerce_sms_consent" type="checkbox" name="mailchimp_woocommerce_sms_consent" value="1"'.($status ? ' checked="checked"' : '').'> ';
            $checkbox .= '<span>' . $label . '</span></label>';
            $checkbox .= '</p>';
            $checkbox .= '<div class="clear"></div>';

            if (is_checkout() && $hide_optin_for_subscriber) {
                $checkbox = '';
            }

            echo apply_filters( 'mailchimp_woocommerce_sms_consent_field', $checkbox, $status, $label);
        }
    }


    /**
     * @param $order_id
     * @param $posted
     */
    public function processSmsConsentField($order_id, $posted)
    {
        $this->handleStatus($order_id);
    }

    public static function isEligibleCountry()
    {
        $store_raw_country  = get_option( 'woocommerce_default_country' );
        $split_country      = explode( ":", $store_raw_country );
        $country = !empty($split_country) && isset( $split_country[0] ) ? $split_country[0] : '';

        return in_array($country, static::$allowedCountries);
    }

    public static function isAllowedToUse()
    {
        $options = mailchimp_get_admin_options();
        $sms_consent_enabled = $options['mailchimp_sms_consent_enabled'] ?? false;

        return  static::isEligibleCountry() && $sms_consent_enabled;
    }

    /**
	 * @param $order
	 */
    public function processPayPalSmsConsentField($order)
    {
        $this->handleStatus($order->get_id());
    }

    /**
     * @param null $order_id
     * @return bool|int
     */
    protected function handleStatus($order_id = null)
    {
        $post_key = 'mailchimp_woocommerce_sms_consent';
        $meta_key = 'mailchimp_woocommerce_sms_consent_subscribed';
        $logged_in = is_user_logged_in();

        // if the post key is available we use it - otherwise we null it out.
        $status = isset($_POST[$post_key]) ? (int) $_POST[$post_key] : null;

        // if the status is null, we don't do anything
        if ($status === null) {
            return false;
        }

        // if we passed in an order id, we update it here.
        if ($order_id) {
            MailChimp_WooCommerce_HPOS::update_order_meta($order_id, $meta_key, $status);
            //update_post_meta($order_id, $meta_key, $status);
        }

        // if the user is logged in, we will update the status correctly.
        if ($logged_in) {
            update_user_meta(get_current_user_id(), $meta_key, $status);
            return $status;
        }

        return false;
    }
}
