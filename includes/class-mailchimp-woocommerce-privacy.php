<?php

class MailChimp_WooCommerce_Privacy
{
    /**
     * Privacy policy
     */
    public function privacy_policy()
    {
        if (function_exists( 'wp_add_privacy_policy_content')) {
            $content = sprintf(/* translators: %s - Mailchimp Privacy Policy URL. */
                __( 'When shopping, we keep a record of your email and the cart contents for up to 30 days on our server. This record is kept to repopulate the contents of your cart if you switch devices or needed to come back another day. Read our privacy policy <a href="%s">here</a>.', 'mailchimp-woocommerce' ),
                'https://mailchimp.com/legal/privacy/'
                
            );
            wp_add_privacy_policy_content('MailChimp for WooCommerce', wp_kses_post(wpautop($content, false)));
        }
    }

    /**
     * @param array $exporters
     * @return mixed
     */
    public function register_exporter($exporters)
    {
        $exporters['mailchimp-woocommerce'] = array(
            'exporter_friendly_name' => __('MailChimp for WooCommerce'),
            'callback'               => array($this, 'export'),
        );
        return $exporters;
    }

    /**
     * @param array $erasers
     * @return mixed
     */
    public function register_eraser($erasers)
    {
        $erasers['mailchimp-woocommerce'] = array(
            'eraser_friendly_name' => __('MailChimp for WooCommerce'),
            'callback'               => array($this, 'erase'),
        );
        return $erasers;
    }

    /**
     * @param $email_address
     * @param int $page
     * @return array
     */
    public function export($email_address, $page = 1)
    {
        global $wpdb;

        $uid = mailchimp_hash_trim_lower($email_address);

        $data = array();

        if (get_site_option('mailchimp_woocommerce_db_mailchimp_carts', false)) {
            $table = "{$wpdb->prefix}mailchimp_carts";
            $statement = "SELECT * FROM $table WHERE id = %s";
            $sql = $wpdb->prepare($statement, $uid);

            if (($saved_cart = $wpdb->get_row($sql)) && !empty($saved_cart)) {
                $data = array('name' => __('Email Address'), 'value' => $email_address);
            }
        }

        // If nothing found, return nothing
        if (is_array($data) && (count($data) < 1)) {
            return (array('data' => array(), 'done' => true));
        }

        return array(
            'data' => array(
                array(
                    'group_id'    => 'mailchimp_cart',
                    'group_label' => __( 'MailChimp Shopping Cart Data', 'mailchimp-woocommerce' ),
                    'item_id'     => 'mailing-shopping-cart-1',
                    'data'        => array(
                        array(
                            'name'  => __( 'User ID', 'mailchimp-woocommerce' ),
                            'value' => $uid,
                        ),
                        $data, // this is already an associative array with name and value keys
                    )
                )
            ),
            'done' => true,
        );
    }

    public function erase($email_address, $page = 1)
    {
        global $wpdb;

        $uid = mailchimp_hash_trim_lower($email_address);
        $count = 0;

        if (get_site_option('mailchimp_woocommerce_db_mailchimp_carts', false)) {
            $table = "{$wpdb->prefix}mailchimp_carts";
            $sql = $wpdb->prepare("DELETE FROM $table WHERE id = %s", $uid);
            $count = $wpdb->query($sql);
        }

        return array(
            'items_removed' => (int) $count,
            'items_retained' => false,
            'messages' => array(),
            'done' => true,
        );
    }
}
