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
     * @param WC_Checkout $checkout
     */
    public function applyNewsletterField($checkout)
    {
        if (!is_admin()) {

            // if the user has chosen to hide the checkbox, don't do anything.
            if (($default_setting = $this->getOption('mailchimp_checkbox_defaults', 'check')) === 'hide') {
                return;
            }

            // allow the user to specify the text in the newsletter label.
            $label = $this->getOption('newsletter_label', __('Subscribe to our newsletter', 'mailchimp-woocommerce'));

            // if the user chose 'check' or nothing at all, we default to true.
            $default_checked = $default_setting === 'check';
            $status = $default_checked;

            // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
            // otherwise we use the default settings.
            if (is_user_logged_in()) {
                $status = get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed', true);
                /// if the user is logged in - and is already subscribed - just ignore this checkbox.
                if ((bool) $status) {
                    return;
                }
                if ($status === '' || $status === null) {
                    $status = $default_checked;
                }
            }

            // echo out the checkbox.
            $checkbox = '<p class="form-row form-row-wide mailchimp-newsletter">';
            $checkbox .= '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="mailchimp_woocommerce_newsletter" type="checkbox" name="mailchimp_woocommerce_newsletter" value="1"'.($status ? ' checked="checked"' : '').'> ';
            $checkbox .= '<label for="mailchimp_woocommerce_newsletter" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline"><span>' . __($label, 'mailchimp-woocommerce') . '</span></label>';
            $checkbox .= '</p>';
            $checkbox .= '<div class="clear"></div>';

            echo apply_filters( 'mailchimp_woocommerce_newsletter_field', $checkbox, $status, $label);
        }
    }

    /**
     * @param $order_id
     * @param $posted
     */
    public function processNewsletterField($order_id, $posted)
    {
        $this->handleStatus($order_id);
    }

    /**
     * @param WC_Order $order
     */
    public function processPayPalNewsletterField($order)
    {
        $this->handleStatus($order->get_id());
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
            update_post_meta($order_id, $meta_key, $status);
        }

        // if the user is logged in, we will update the status correctly.
        if ($logged_in) {
            update_user_meta(get_current_user_id(), $meta_key, $status);
            return $status;
        }

        return false;
    }
}
