<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 2/22/16
 * Time: 9:09 AM
 */
class MailChimp_Newsletter extends MailChimp_Woocommerce_Options
{
    /**
     * @param WC_Checkout $checkout
     */
    public function applyNewsletterField($checkout)
    {
        if (!is_admin()) {
            $status = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'mailchimp_woocommerce_is_subscribed',
                true) : true;

            $checkbox = '<p class="form-row form-row-wide create-account">';
            $checkbox .= '<input class="input-checkbox" id="mailchimp_woocommerce_newsletter" type="checkbox" name="mailchimp_woocommerce_newsletter" value="1" checked="' . ($status ? 'checked' : '') . '"> ';
            $checkbox .= '<label for="mailchimp_woocommerce_newsletter" class="checkbox">' . $this->getOption('newsletter_label',
                    'Subscribe to our newsletter') . '</label></p>';
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
        $this->handleStatus($order->id);
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
