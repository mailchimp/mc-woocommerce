<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
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

        $this->assignIP();
    }

    /**
     * @return null
     */
    public function assignIP()
    {
        $this->ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded_address = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
            $this->ip_address = $forwarded_address[0];
        }

        return $this->ip_address;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        if (($result = $this->process())) {
            mailchimp_log('ac.success', 'Added', array('api_response' => $result->toArray()));
        }

        return false;
    }

    /**
     * @return bool|MailChimp_WooCommerce_Cart
     */
    public function process()
    {
        try {

            if (!mailchimp_is_configured() || !($api = mailchimp_get_api())) {
                mailchimp_debug(get_called_class(), 'mailchimp is not configured properly');
                return false;
            }

            $options = get_option('mailchimp-woocommerce', array());
            $store_id = mailchimp_get_store_id();

            $this->cart_data = json_decode($this->cart_data, true);

            // delete it and the add it back.
            $api->deleteCartByID($store_id, $this->unique_id);

            // if they emptied the cart ignore it.
            if (!is_array($this->cart_data) || empty($this->cart_data)) {
                return false;
            }

            $checkout_url = wc_get_checkout_url();

            if (mailchimp_string_contains($checkout_url, '?')) {
                $checkout_url .= '&mc_cart_id='.$this->unique_id;
            } else {
                $checkout_url .= '?mc_cart_id='.$this->unique_id;
            }

            $customer = new MailChimp_WooCommerce_Customer();
            $customer->setId($this->unique_id);
            $customer->setEmailAddress($this->email);
            $customer->setOptInStatus(false);

            $cart = new MailChimp_WooCommerce_Cart();
            $cart->setId($this->unique_id);

            // if we have a campaign id let's set it now.
            if (!empty($this->campaign_id)) {
                try {
                    $cart->setCampaignID($this->campaign_id);
                }
                catch (\Exception $e) {
                    mailchimp_log('cart_set_campaign_id.error', 'No campaign added to abandoned cart, with provided ID: '. $this->campaign_id. ' :: '. $e->getMessage(). ' :: in '.$e->getFile().' :: on '.$e->getLine());
                }
            }

            $cart->setCheckoutUrl($checkout_url);
            $cart->setCurrencyCode(isset($options['store_currency_code']) ? $options['store_currency_code'] : 'USD');

            $cart->setCustomer($customer);

            $order_total = 0;
            $products = array();

            foreach ($this->cart_data as $hash => $item) {
                try {
                    $line = $this->transformLineItem($hash, $item);
                    $cart->addItem($line);
                    $order_total += ($item['quantity'] * $line->getPrice());
                    $products[] = $line;
                } catch (\Exception $e) {}
            }

            if (empty($products)) {
                return false;
            }

            $cart->setOrderTotal($order_total);

            try {
                // if the post is successful we're all good.
                if ($api->addCart($store_id, $cart, false) !== false) {
                    mailchimp_log('abandoned_cart.success', "email: {$customer->getEmailAddress()} :: checkout_url: $checkout_url");
                }
            } catch (\Exception $e) {

                mailchimp_error('abandoned_cart.error', "email: {$customer->getEmailAddress()} :: attempting product update :: {$e->getMessage()}");

                // if we have an error it's most likely due to a product not being found.
                // let's loop through each item, verify that we have the product or not.
                // if not, we will add it.
                foreach ($products as $item) {
                    /** @var MailChimp_WooCommerce_LineItem $item */
                    $transformer = new MailChimp_WooCommerce_Single_Product($item->getProductID());
                    if (!$transformer->api()->getStoreProduct($store_id, $item->getProductId())) {
                        $transformer->handle();
                    }
                }

                // if the post is successful we're all good.
                $api->addCart($store_id, $cart, false);

                mailchimp_log('abandoned_cart.success', "email: {$customer->getEmailAddress()}");
            }

        } catch (MailChimp_WooCommerce_RateLimitError $e) {
            sleep(3);
            $this->release();
            mailchimp_error('cart.error', mailchimp_error_trace($e, "RateLimited :: email {$this->email}"));
        } catch (\Exception $e) {
            update_option('mailchimp-woocommerce-cart-error', $e->getMessage());
            mailchimp_error('abandoned_cart.error', $e);
        }

        return false;
    }

    /**
     * @param string $hash
     * @param $item
     * @return MailChimp_WooCommerce_LineItem
     */
    protected function transformLineItem($hash, $item)
    {
        $product = wc_get_product($item['product_id']);
        $price = $product ? $product->get_price() : 0;

        $line = new MailChimp_WooCommerce_LineItem();
        $line->setId($hash);
        $line->setProductId($item['product_id']);

        if (isset($item['variation_id']) && $item['variation_id'] > 0) {
            $line->setProductVariantId($item['variation_id']);
        } else {
            $line->setProductVariantId($item['product_id']);
        }

        $line->setQuantity($item['quantity']);
        $line->setPrice($price);

        return $line;
    }
}
