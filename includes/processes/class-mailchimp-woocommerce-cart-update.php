<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Cart_Update extends WP_Job
{
    public $unique_id;
    public $email;
    public $previous_email;
    public $campaign_id;
    public $cart_data;
    public $ip_address;

    /**
     * MailChimp_WooCommerce_Cart_Update constructor.
     * @param null $uid
     * @param null $email
     * @param null $campaign_id
     * @param array $cart_data
     */
    public function __construct($uid = null, $email = null, $campaign_id = null, array $cart_data = array())
    {
        if ($uid) {
            $this->unique_id = $uid;
        }
        if ($email) {
            $this->email = $email;
        }
        if (!empty($cart_data)) {
            $this->cart_data = json_encode($cart_data);
        }

        if ($campaign_id) {
            $this->campaign_id = $campaign_id;
        }

        $this->ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        try {
            $options = get_option('mailchimp-woocommerce', array());
            $store_id = mailchimp_get_store_id();

            if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

                $this->cart_data = json_decode($this->cart_data, true);

                $api = new MailChimpApi($options['mailchimp_api_key']);

                // delete it and the add it back.
                $api->deleteCartByID($store_id, $this->unique_id);

                // if they emptied the cart ignore it.
                if (!is_array($this->cart_data) || empty($this->cart_data)) {
                    return false;
                }

                $customer = new MailChimp_Customer();
                $customer->setId($this->unique_id);
                $customer->setEmailAddress($this->email);
                $customer->setOptInStatus(true);

                $cart = new MailChimp_Cart();
                $cart->setId($this->unique_id);
                $cart->setCampaignID($this->campaign_id);
                $cart->setCheckoutUrl(wc_get_checkout_url());
                $cart->setCurrencyCode(isset($options['store_currency_code']) ? $options['store_currency_code'] : 'USD');

                $cart->setCustomer($customer);

                $order_total = 0;

                foreach ($this->cart_data as $hash => $item) {
                    try {
                        $line = new MailChimp_LineItem();
                        $line->setId($hash);
                        $line->setProductId($item['product_id']);
                        $line->setProductVariantId($item['data']->post_parent > 0 ? $item['data']->post_parent : $item['product_id']);
                        $line->setQuantity($item['quantity']);
                        $line->setPrice($item['data']['price']);

                        $cart->addItem($line);

                        $order_total += ($item['quantity'] * $line->getPrice());

                    } catch (\Exception $e) { }
                }

                $cart->setOrderTotal($order_total);

                // update or create the cart.
                if (($response = $api->addCart($store_id, $cart))) {
                    slack()->notice('Cart Data Submitted :: '.$this->email.' :: '."\n".(print_r($response->toArray(), true)));
                }

                return false;
            }
        } catch (\Exception $e) {
            update_option('mailchimp-woocommerce-cart-error', $e->getMessage());
            slack()->notice("Abandoned Cart Error :: {$e->getMessage()} on {$e->getLine()} in {$e->getFile()}");
        }

        return false;
    }
}
