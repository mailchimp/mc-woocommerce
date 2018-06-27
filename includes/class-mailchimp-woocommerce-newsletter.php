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
            $label = $this->getOption('newsletter_label', 'Subscribe to our newsletter');

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

            echo $checkbox;
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
        $status = isset($_POST['mailchimp_woocommerce_newsletter']) ? (int)$_POST['mailchimp_woocommerce_newsletter'] : 0;

        if ($order_id) {
            update_post_meta($order_id, 'mailchimp_woocommerce_is_subscribed', $status);
        }

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed', $status);
            
            return $status;
        }

        return false;
    }
}
