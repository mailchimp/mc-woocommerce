<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/22/16
 * Time: 9:09 AM
 */

class MailChimp_Newsletter extends MailChimp_WooCommerce_Options
{
    /** @var null|static */
    protected static $_instance = null;

    /**
     * @return MailChimp_Newsletter
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) {
            return static::$_instance;
        }
        $env = mailchimp_environment_variables();
        static::$_instance = new MailChimp_Newsletter();
        static::$_instance->setVersion($env->version);
        return static::$_instance;
    }

	/**
	 * @param $checkout
	 */
    public function applyNewsletterField($checkout)
    {
        // some folks have asked to be able to check out on behalf of customers. I guess this makes sense
        // if they want to do this, but it needs to be a constant and custom.
        $allow_admin = defined('MAILCHIMP_ALLOW_ADMIN_NEWSLETTER') && MAILCHIMP_ALLOW_ADMIN_NEWSLETTER;

        if ($allow_admin || !is_admin()) {
            $api = mailchimp_get_api();

            // get the gdpr fields from the cache - or call it again and save for 5 minutes.
            $GDPRfields = $api->getCachedGDPRFields(mailchimp_get_list_id());

            // if the user has chosen to hide the checkbox, don't do anything.
            if (($default_setting = $this->getOption('mailchimp_checkbox_defaults', 'check')) === 'hide') {
                return;
            }

            // allow the user to specify the text in the newsletter label.
            $label = $this->getOption('newsletter_label');
            if ($label == '') $label = __('Subscribe to our newsletter', 'mailchimp-for-woocommerce');
            // if the user chose 'check' or nothing at all, we default to true.
            $default_checked = $default_setting === 'check';
            $gdpr_statuses = false;
            $status = $default_checked;
            $hide_optin_for_subscriber = false;

            // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
            // otherwise we use the default settings.
            if (is_user_logged_in()) {
                $status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed', true);
                $gdpr_statuses = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_gdpr_fields', true);
                $hide_optin_for_subscriber = $status === true || $status === '1';

                /// if the user is logged in - and is already subscribed - just ignore this checkbox.
                if ($status === '' || $status === null) {
                    $status = $default_checked;
                }
            }


            // echo out the subscription checkbox.
            $checkbox = '<p class="form-row form-row-wide mailchimp-newsletter">';
            $checkbox .= '<label for="mailchimp_woocommerce_newsletter" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline">';
            $checkbox .= '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="mailchimp_woocommerce_newsletter" type="checkbox" name="mailchimp_woocommerce_newsletter" value="1"'.($status ? ' checked="checked"' : '').'> ';
            $checkbox .= '<span>' . $label . '</span></label>';
            $checkbox .= '</p>';
            $checkbox .= '<div class="clear"></div>';

            // only render these fields if it's an array that has valid data.
            if (!empty($GDPRfields) && is_array($GDPRfields)) {
                $checkbox .= "<div style='display: " . ($gdpr_statuses ? 'none' : 'block') . "'>";
                $checkbox .= "<div id='mailchimp-gdpr-fields'><p>";
                $checkbox .= __('Please select all the ways you would like to hear from us', 'mailchimp-for-woocommerce');
                $checkbox .= "<div class='clear' ></div>";

                foreach ($GDPRfields as $key => $field) {
                    $marketing_permission_id = $field['marketing_permission_id'];

                    $gdpr_checked = $field['enabled'];
                    $text = $field['text'];

                    // Add to the checkbox output
                    $checkbox .= "<input type='hidden' value='0' name='mailchimp_woocommerce_gdpr[{$marketing_permission_id}]'>";
                    $checkbox .= "<label for='mailchimp_woocommerce_gdpr[{$marketing_permission_id}]' class='woocommerce-form__label woocommerce-form__label-for-checkbox inline'>";
                    $checkbox .= "<input class='woocommerce-form__input woocommerce-form__input-checkbox input-checkbox' id='mailchimp_woocommerce_gdpr[{$marketing_permission_id}]' type='checkbox' name='mailchimp_woocommerce_gdpr[{$marketing_permission_id}]' value='1'".($gdpr_checked ? ' checked="checked"' : '').">";
                    $checkbox .= "<span>{$text}</span></label>";
                    $checkbox .= "<div class='clear'></div>";
                }
                $checkbox .= "</p></div>";
                $checkbox .= "</div>";
            }

            if (is_checkout() && $hide_optin_for_subscriber) {
                $checkbox = '';
            }

            echo apply_filters( 'mailchimp_woocommerce_newsletter_field', $checkbox, $status, $label);

            // Render SMS consent fields after newsletter checkbox
            $this->applySmsConsentField();
        }
    }

    /**
     * Render SMS consent checkbox and phone field for classic checkout
     */
    public function applySmsConsentField()
    {
        // Check if SMS is enabled in settings
        if (!$this->isSmsEnabled()) {
            return;
        }

        // Check if merchant has approved SMS application
        if (!$this->merchantHasSmsApproved()) {
            return;
        }

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
            $user_sms_status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_subscribed', true);
            $sms_phone = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_sms_phone', true);
            $hide_sms_for_subscriber = $user_sms_status === true || $user_sms_status === '1';

            if ($user_sms_status === '' || $user_sms_status === null) {
                $sms_status = $sms_default_checked;
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
        $sms_html .= '<div id="mailchimp-sms-phone-wrapper" class="form-row form-row-wide" style="display: ' . ($sms_status ? 'block' : 'none') . '; margin-left: 28px;">';
        $sms_html .= '<label for="mailchimp_woocommerce_sms_phone">' . __('SMS Phone Number', 'mailchimp-for-woocommerce') . ' <abbr class="required" title="required">*</abbr></label>';
        $sms_html .= '<input type="tel" class="input-text" id="mailchimp_woocommerce_sms_phone" name="mailchimp_woocommerce_sms_phone" placeholder="+1 (555) 123-4567" value="' . esc_attr($sms_phone) . '">';
        $sms_html .= '<small class="mailchimp-sms-disclaimer" style="display: block; color: #666; font-size: 12px; margin-top: 8px; line-height: 1.4;">' . esc_html($sms_disclaimer) . '</small>';
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
                var smsPhoneInput = $("#mailchimp_woocommerce_sms_phone");
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
                
                // Validation on checkout
                $("form.checkout").on("checkout_place_order", function() {
                    if (smsCheckbox.is(":checked") && !smsPhoneInput.val().trim()) {
                        alert("' . esc_js(__('Please enter a phone number for SMS consent.', 'mailchimp-for-woocommerce')) . '");
                        smsPhoneInput.focus();
                        return false;
                    }
                    return true;
                });
            });
        </script>';

        echo apply_filters('mailchimp_woocommerce_sms_consent_field', $sms_html, $sms_status, $sms_label);
    }

    /**
     * Check if SMS marketing is enabled
     *
     * @return bool
     */
    public function isSmsEnabled()
    {
        return (bool) $this->getOption('mailchimp_sms_enabled', false);
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
     * Get SMS sending countries for the merchant
     *
     * @return array
     */
    public function getSmsSendingCountries()
    {
        try {
            if (!mailchimp_is_configured()) {
                return array();
            }
            $list_id = mailchimp_get_list_id();
            if (!$list_id) {
                return array();
            }
            $api = mailchimp_get_api();
            $sms_status = $api->getCachedSmsApplicationStatus($list_id);
            if ($sms_status && !empty($sms_status['sending_countries'])) {
                return $sms_status['sending_countries'];
            }
            return array();
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Check if a country is eligible for SMS
     *
     * @param string $country_code 2-letter country code
     * @return bool
     */
    public function isCountryEligibleForSms($country_code)
    {
        $sending_countries = $this->getSmsSendingCountries();
        if (empty($sending_countries)) {
            // If no countries configured, allow all (graceful fallback)
            return true;
        }
        return in_array(strtoupper($country_code), $sending_countries, true);
    }

    /**
     * Get the audience name for disclaimer
     *
     * @return string
     */
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
     * @param $order_id
     * @param $posted
     */
    public function processNewsletterField($order_id, $posted)
    {
        $this->handleStatus($order_id);
        $this->handleSmsStatus($order_id);
    }

	/**
	 * @param $order
	 */
    public function processPayPalNewsletterField($order)
    {
        $this->handleStatus($order->get_id());
        $this->handleSmsStatus($order->get_id());
    }

    /**
     * @param $sanitized_user_login
     * @param $user_email
     * @param $reg_errors
     */
    public function processRegistrationForm($sanitized_user_login, $user_email, $reg_errors)
    {
        if (defined('WOOCOMMERCE_CHECKOUT')) {
            return; // Ship checkout
        }

        $this->handleStatus();
        $this->handleSmsStatus();
    }

    /**
     * @param null $order_id
     * @return bool|int
     */
    protected function handleStatus($order_id = null)
    {
        $post_key = 'mailchimp_woocommerce_newsletter';
        $meta_key = 'mailchimp_woocommerce_is_subscribed';
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
        $sms_phone_key = 'mailchimp_woocommerce_sms_phone';
        $sms_subscribed_meta = 'mailchimp_woocommerce_sms_subscribed';
        $sms_phone_meta = 'mailchimp_woocommerce_sms_phone';
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

        $sms_subscribed = $wc_order->get_meta('mailchimp_woocommerce_sms_subscribed');
        $sms_phone = $wc_order->get_meta('mailchimp_woocommerce_sms_phone');

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
        $sms_subscribed = get_user_meta($user_id, 'mailchimp_woocommerce_sms_subscribed', true);
        $sms_phone = get_user_meta($user_id, 'mailchimp_woocommerce_sms_phone', true);

        if (!$sms_subscribed || empty($sms_phone)) {
            return false;
        }

        return array(
            'subscribed' => (bool) $sms_subscribed,
            'phone' => $sms_phone,
        );
    }
}
