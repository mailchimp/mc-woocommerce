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
        // Check if SMS is enabled in settings
        if (!$this->isSmsEnabled()) {
            return;
        }

        // Check if merchant has approved SMS application
//        if (!$this->merchantHasSmsApproved()) {
//            return;
//        }

        // Compliance: checkbox must always be unchecked by default, label and disclaimer are fixed
        $sms_label = __('Text me with news and offers', 'mailchimp-for-woocommerce');

        $audience_name = $this->getAudienceName();
        $prefix = !empty($audience_name) ? $audience_name . ' – ' : '';
        $sms_disclaimer = $prefix . __('By providing your phone number, you agree to receive promotional and marketing messages, notifications, and customer service communications. Message & data rates may apply. Consent is not a condition of purchase. Message frequency may vary. You can unsubscribe at any time by replying STOP.', 'mailchimp-for-woocommerce');

        // Always unchecked by default per compliance
        $sms_status = false;
        $sms_phone = '';
        $hide_sms_for_subscriber = false;

        // Check logged-in user's SMS subscription status
        if (is_user_logged_in()) {
            $user_sms_status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_consent_subscribed', true);
            $sms_phone = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_consent_phone', true);
            $hide_sms_for_subscriber = $user_sms_status === true || $user_sms_status === '1';

            if ($user_sms_status === '' || $user_sms_status === null) {
                $sms_status = false;
            } else {
                $sms_status = (bool) $user_sms_status;
            }
        }

        // Don't show if already subscribed to SMS
        if (is_checkout() && $hide_sms_for_subscriber) {
            return;
        }

        // Build SMS consent HTML
        $sms_html = '<div class="mailchimp-sms-consent" style="margin-top: 15px;">';

        // SMS Checkbox
        $sms_html .= '<p class="form-row form-row-wide mailchimp-sms-checkbox">';
        $sms_html .= '<label for="mailchimp_woocommerce_sms_subscribe" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline">';
        $sms_html .= '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="mailchimp_woocommerce_sms_subscribe" type="checkbox" name="mailchimp_woocommerce_sms_subscribe" value="1"' . ($sms_status ? ' checked="checked"' : '') . '> ';
        $sms_html .= '<span>' . esc_html($sms_label) . '</span></label>';
        $sms_html .= '</p>';

        // SMS Phone field (conditionally displayed via JS)
        $sms_html .= '<div id="mailchimp-sms-phone-wrapper" class="form-row form-row-wide from-sms" style="display: ' . ($sms_status ? 'block' : 'none') . '; margin-left: 28px;">';
        $sms_html .= '<label for="mailchimp_woocommerce_sms_consent_phone">' . __('SMS Phone Number', 'mailchimp-for-woocommerce') . ' <abbr class="required" title="required">*</abbr></label>';
        $sms_html .= '<input type="tel" class="input-text" id="mailchimp_woocommerce_sms_consent_phone" name="mailchimp_woocommerce_sms_consent_phone" placeholder="+1 (555) 123-4567" value="' . esc_attr($sms_phone) . '">';
        $sms_html .= '<small class="mailchimp-sms-disclaimer" style="display: block; color: #666; font-size: 12px; margin-top: 8px; margin-bottom: 8px; line-height: 1.4;">' . esc_html($sms_disclaimer) . '</small>';
        $sms_html .= '</div>';

        $sms_html .= '</div>';
        $sms_html .= '<div class="clear"></div>';

        // Get SMS sending countries for JS
        $sms_countries = $this->getSmsSendingCountries();
        $sms_countries_json = !empty($sms_countries) ? json_encode($sms_countries) : '[]';

        // JavaScript to toggle phone field visibility, validation, and country filtering
        $sms_html .= '<script type="text/javascript">
            jQuery(document).ready(function($) {
                var smsCheckbox = $("#mailchimp_woocommerce_sms_subscribe");
                var smsPhoneWrapper = $("#mailchimp-sms-phone-wrapper");
                var smsPhoneInput = $("#mailchimp_woocommerce_sms_consent_phone");
                var smsConsentWrapper = $(".mailchimp-sms-consent");
                var smsSendingCountries = ' . $sms_countries_json . ';
                  
                function isCountryEligible(countryCode) {
                    // If no countries configured, allow all
                    if (!smsSendingCountries || smsSendingCountries.length === 0) {
                        return true;
                    }
                    return smsSendingCountries.indexOf(countryCode.toUpperCase()) !== -1;
                }
                
                function checkBillingCountry() {
                    var billingCountry = $("#billing_country").val();
                    if (billingCountry && !isCountryEligible(billingCountry)) {
                        smsConsentWrapper.slideUp();
                        smsCheckbox.prop("checked", false);
                        smsPhoneInput.prop("required", false).val("");
                    } else {
                        smsConsentWrapper.slideDown();
                    }
                }
                
                function toggleSmsPhone() {
                    if (smsCheckbox.is(":checked")) {
                        smsPhoneWrapper.slideDown();
                        smsPhoneInput.prop("required", true);
                    } else {
                        smsPhoneWrapper.slideUp();
                        smsPhoneInput.prop("required", false).val("");
                    }
                }
                
                smsCheckbox.on("change", toggleSmsPhone);
                toggleSmsPhone();
                
                // Watch for billing country changes
                $("#billing_country").on("change", checkBillingCountry);
                $(document.body).on("updated_checkout", checkBillingCountry);
                checkBillingCountry();
                
                function mailchimpValidateSmsPhone(value) {
                    console.log("validate_callback for smsPhone", { value });
               
                    // 1) Type check (match PHP logic)
                    if (value !== null && typeof value !== "string") {
                        return {
                            error: "api-error",
                            message: "SMS phone must be a string"
                        };
                    }
                    // 2) Only validate if not empty
                    if (value && value.length > 0) {
                        // Remove spaces, dashes, parentheses (same as preg_replace)
                        const cleaned = value.replace(/[\s\-\(\)]/g, "");
                        // 3) Same regex as PHP
                        const phoneRegex = /^\+?[1-9]\d{6,14}$/;
                        if (!phoneRegex.test(cleaned)) {
                            return {
                                error: "api-error",
                                message: "Invalid phone number format"
                            };
                        }
                    }
                    return true;
                }
                
                // Validation on checkout
                $("form.checkout").on("checkout_place_order", function() {
                  
                  if (!smsCheckbox.is(":checked")) {
                        console.log("no sms consent, skipping validation");
                        return true;
                    }
                    if (smsCheckbox.is(":checked")) {
                        const value_sms = smsPhoneInput.val().trim();
                        console.log("about to check SMS phone number", value_sms);
                        if (!value_sms) {
                            alert("' . esc_js(__('Please enter a phone number for SMS consent. This is not good.', 'mailchimp-for-woocommerce')) . '");
                            smsPhoneInput.focus();
                            return false;
                        }
                        const result = mailchimpValidateSmsPhone(value_sms);
                        if (result !== true) {
                            smsPhoneInput.focus();
                            alert(result.message || "Invalid SMS phone number.");
                            return false;
                        } else {
                            console.log("SMS phone was valid", value_sms);
                        }
                        return false;
                    }
                    return true;
                });
            });
        </script>';

        echo apply_filters('mailchimp_woocommerce_sms_consent_field', $sms_html, $sms_status, $sms_label);
    }

    public function isSmsEnabled()
    {
        return (bool) $this->getOption('mailchimp_sms_consent_enabled', false);
    }

    public function getSmsSendingCountries()
    {
        return self::$allowedCountries;
    }

    public function getSmsProgram()
    {
        try {
            if (!mailchimp_is_configured()) {
                return new MailChimp_WooCommerce_SmsProgram();
            }
            $list_id = mailchimp_get_list_id();
            if (!$list_id) {
                return new MailChimp_WooCommerce_SmsProgram();
            }
            $api = mailchimp_get_api();
            return $api->getCachedSmsProgram($list_id);
        } catch (Exception $e) {
            mailchimp_debug('api', "error getting sms program: {$e->getMessage()}");
            return new MailChimp_WooCommerce_SmsProgram();
        }
    }

    public static function isSmsProgramActive()
    {
        $handler = static::instance();
        $program = $handler->getSmsProgram();
        return $program ? $program->isActive() : false;
    }

    public function isCountryEligibleForSms($country_code)
    {
        $sending_countries = $this->getSmsSendingCountries();
        if (empty($sending_countries)) {
            // If no countries configured, allow all (graceful fallback)
            return true;
        }
        return in_array(strtoupper($country_code), $sending_countries, true);
    }

    protected function getAudienceName()
    {
        try {
            if (!mailchimp_is_configured()) {
                return '';
            }
            $list_id = mailchimp_get_list_id();
            if (!$list_id) {
                return '';
            }
            $api = mailchimp_get_api();
            $list = $api->getList($list_id);
            return isset($list['name']) ? $list['name'] : '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Check if merchant has an approved SMS application
     *
     * @return bool
     */
    public function merchantHasSmsApproved()
    {
        try {
            if (!mailchimp_is_configured()) {
                return false;
            }
            $list_id = mailchimp_get_list_id();
            if (!$list_id) {
                return false;
            }
            $api = mailchimp_get_api();
            $sms_status = $api->getCachedSmsApplicationStatus($list_id);
            return $sms_status && !empty($sms_status['enabled']);
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * @param $order_id
     * @param $posted
     */
    public function processSmsConsentField($order_id, $posted)
    {
        $this->handleSmsStatus($order_id);
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
        $this->handleSmsStatus($order->get_id());
    }

    /**
     * Handle SMS subscription status from classic checkout
     *
     * @param null $order_id
     * @return bool
     */
    protected function handleSmsStatus($order_id = null)
    {
        // Check if SMS is enabled
        if (!$this->isSmsEnabled()) {
            return false;
        }

        $sms_checkbox_key = 'mailchimp_woocommerce_sms_subscribe';
        $sms_phone_key = 'mailchimp_woocommerce_sms_consent_phone';
        $sms_subscribed_meta = 'mailchimp_woocommerce_sms_consent_subscribed';
        $sms_phone_meta = 'mailchimp_woocommerce_sms_consent_phone';
        $logged_in = is_user_logged_in();

        // Get SMS consent status from POST
        $sms_subscribed = isset($_POST[$sms_checkbox_key]) ? (bool) $_POST[$sms_checkbox_key] : false;
        $sms_phone = isset($_POST[$sms_phone_key]) ? sanitize_text_field($_POST[$sms_phone_key]) : '';

        // Sanitize phone number - keep only + and digits
        $sms_phone = preg_replace('/[^\+\d]/', '', $sms_phone);

        // If they didn't check the box or didn't provide a phone, don't save anything
        if (!$sms_subscribed || empty($sms_phone)) {
            return false;
        }

        // Update order meta
        if ($order_id) {
            MailChimp_WooCommerce_HPOS::update_order_meta($order_id, $sms_subscribed_meta, true);
            MailChimp_WooCommerce_HPOS::update_order_meta($order_id, $sms_phone_meta, $sms_phone);
        }

        // Update user meta if logged in
        if ($logged_in) {
            update_user_meta(get_current_user_id(), $sms_subscribed_meta, true);
            update_user_meta(get_current_user_id(), $sms_phone_meta, $sms_phone);
        }

        return true;
    }

    /**
     * Get SMS subscription data from order
     *
     * @param int $order_id
     * @return array|false
     */
    public static function getSmsDataFromOrder($order_id)
    {
        $wc_order = wc_get_order($order_id);
        if (!$wc_order) {
            return false;
        }

        $sms_subscribed = $wc_order->get_meta('mailchimp_woocommerce_sms_consent_subscribed');
        $sms_phone = $wc_order->get_meta('mailchimp_woocommerce_sms_consent_phone');

        if (!$sms_subscribed || empty($sms_phone)) {
            return false;
        }

        return array(
            'subscribed' => (bool) $sms_subscribed,
            'phone' => $sms_phone,
        );
    }

    /**
     * Get SMS subscription data from user
     *
     * @param int $user_id
     * @return array|false
     */
    public static function getSmsDataFromUser($user_id)
    {
        $sms_subscribed = get_user_meta($user_id, 'mailchimp_woocommerce_sms_consent_subscribed', true);
        $sms_phone = get_user_meta($user_id, 'mailchimp_woocommerce_sms_consent_phone', true);

        if (!$sms_subscribed || empty($sms_phone)) {
            return false;
        }

        return array(
            'subscribed' => (bool) $sms_subscribed,
            'phone' => $sms_phone,
        );
    }
}
