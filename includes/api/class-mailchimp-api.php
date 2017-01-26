<?php

/**
 * Created by PhpStorm.
 *
 * User: kingpin
 * Email: ryan@mailchimp.com
 * Date: 11/4/15
 * Time: 3:35 PM
 */
class MailChimp_WooCommerce_MailChimpApi
{
    protected $version = '3.0';
    protected $data_center = 'us2';
    protected $api_key = null;
    protected $auth_type = 'key';

    /**
     * MailChimpService constructor.
     * @param null $api_key
     */
    public function __construct($api_key = null)
    {
        if (!empty($api_key)) {
            $this->setApiKey($api_key);
        }
    }

    /**
     * @param $key
     * @return $this
     */
    public function setApiKey($key)
    {
        $parts = str_getcsv($key, '-');

        if (count($parts) == 2) {
            $this->data_center = $parts[1];
        }

        $this->api_key = $parts[0];

        return $this;
    }

    /**
     * @param $dc
     * @return $this
     */
    public function setDataCenter($dc)
    {
        $this->data_center = $dc;

        return $this;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param bool $return_profile
     * @return array|bool
     */
    public function ping($return_profile = false)
    {
        try {
            $profile = $this->get('/');
            return $return_profile ? $profile : true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getProfile()
    {
        return $this->get('/');
    }

    /**
     * @return array|bool
     */
    public function getAuthorizedApps()
    {
        return $this->get('authorized-apps');
    }

    /**
     * @return array|bool
     */
    public function getAuthorizedAppDetails($id)
    {
        return $this->get("authorized-apps/$id");
    }

    /**
     * Returns an array of ['access_token' => '', 'viewer_token' => '']
     *
     * @param $client_id
     * @param $client_secret
     * @return array|bool
     */
    public function linkAuthorizedApp($client_id, $client_secret)
    {
        return $this->post('authorized-apps', array('client_id' => $client_id, 'client_secret' => $client_secret));
    }

    /**
     * @param $list_id
     * @param $email
     * @return array|bool
     */
    public function member($list_id, $email)
    {
        $hash = md5(strtolower($email));
        return $this->get("lists/$list_id/members/$hash", array());
    }

    /**
     * @param $list_id
     * @return array|bool
     */
    public function members($list_id)
    {
        return $this->get("lists/$list_id/members");
    }

    /**
     * @param $list_id
     * @param $email
     * @return array|bool
     */
    public function deleteMember($list_id, $email)
    {
        $hash = md5(strtolower($email));
        return $this->delete("lists/$list_id/members/$hash", array());
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function subscribe($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $data = array(
            'email_type' => 'html',
            'email_address' => $email,
            'status' => ($subscribed === true ? 'subscribed' : 'pending'),
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }

        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->post("lists/$list_id/members", $data);
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function update($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $hash = md5(strtolower($email));

        $data = array(
            'email_address' => $email,
            'status' => ($subscribed === null ? 'cleaned' : ($subscribed === true ? 'subscribed' : 'unsubscribed')),
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }


        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->patch("lists/$list_id/members/$hash", $data);
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function updateOrCreate($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $hash = md5(strtolower($email));

        $data = array(
            'email_address' => $email,
            'status' => ($subscribed === null ? 'cleaned' : ($subscribed === true ? 'subscribed' : 'unsubscribed')),
            'status_if_new' => ($subscribed === true ? 'subscribed' : 'pending'),
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }

        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->put("lists/$list_id/members/$hash", $data);
    }

    /**
     * @param MailChimp_WooCommerce_CreateListSubmission $submission
     * @return array|bool
     */
    public function createList(MailChimp_WooCommerce_CreateListSubmission $submission)
    {
        return $this->post('lists', $submission->getSubmission());
    }

    /**
     * @param bool $as_list
     * @param int $count
     * @return array|mixed
     */
    public function getLists($as_list = false, $count = 100)
    {
        $result = $this->get('lists', array('count' => $count));

        if ($as_list) {
            $lists = array();
            if ($result) {
                $result = (object)$result;
                if (isset($result->lists) && is_array($result->lists)) {
                    foreach ($result->lists as $list) {
                        $list = (object)$list;
                        $lists[$list->id] = $list->name;
                    }
                }
            }

            return $lists;
        }

        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasList($id)
    {
        try {
            return (bool) $this->getList($id);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param $id
     * @return mixed
     */
    public function getList($id)
    {
        return $this->get('lists/' . $id);
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function deleteList($id)
    {
        return $this->delete('lists/'.$id);
    }

    /**
     * @return array|mixed
     */
    public function getListsWithMergeFields()
    {
        $lists = $this->getLists(true);
        foreach ($lists as $id => $name) {
            $lists[$id] = $this->mergeFields($id, 100);
        }

        return $lists;
    }

    /**
     * @param $list_id
     * @param int $count
     * @return array|bool
     */
    public function mergeFields($list_id, $count = 10)
    {
        $result = $this->get("lists/$list_id/merge-fields", array('count' => $count,));

        return $result;
    }

    /**
     * @param $list_id
     * @return array|bool
     */
    public function getInterestGroups($list_id)
    {
        if (empty($list_id)) {
            return array();
        }
        $result = $this->get("lists/$list_id/interest-categories");

        return $result;
    }

    /**
     * @param $list_id
     * @param $group_id
     * @return array|bool
     */
    public function getInterestGroupOptions($list_id, $group_id)
    {
        if (empty($list_id) || empty($group_id)) {
            return array();
        }
        $result = $this->get("lists/$list_id/interest-categories/$group_id/interests");

        return $result;
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @param DateTime|null $since
     * @param null $campaign_id
     * @return array|bool
     */
    public function orders($store_id, $page = 1, $count = 10, \DateTime $since = null, $campaign_id = null)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/orders', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
            'since' => ($since ? $since->format('Y-m-d H:i:s') : null),
            'cid' => $campaign_id,
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @return MailChimp_WooCommerce_Store|bool
     */
    public function getStore($store_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id");
            if (!isset($data['id']) || !isset($data['name'])) {
                return false;
            }
            $store = new MailChimp_WooCommerce_Store();
            return $store->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function stores()
    {
        try {
            $data = $this->get("ecommerce/stores", array('count' => 50));

            if (!isset($data['stores']) || empty($data['stores'])) {
                return array();
            }

            $response = array();

            foreach ($data['stores'] as $store_data) {
                $store = new MailChimp_WooCommerce_Store();
                $response[] = $store->fromArray($store_data);
            }

            return $response;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $is_syncing
     * @return array|bool|mixed|null|object
     */
    public function flagStoreSync($store_id, $is_syncing)
    {
        try {
            // pull the store to make sure we have one.
            if (!($store = $this->getStore($store_id))) {
                return false;
            }

            // flag it as ^^^ is_syncing ^^^
            $store->flagSyncing($is_syncing);

            // patch the store data
            return $this->patch("ecommerce/stores/{$store_id}", $store->toArray());

        } catch (\Exception $e) {
            mailchimp_log('flag.store_sync', $e->getMessage(). ' :: in '.$e->getFile().' :: on '.$e->getLine());
        }
        return false;
    }

    /**
     * @param MailChimp_WooCommerce_Store $store
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Store
     * @throws Exception
     */
    public function addStore(MailChimp_WooCommerce_Store $store, $silent = true)
    {
        try {
            $this->validateStoreSubmission($store);
            $data = $this->post("ecommerce/stores", $store->toArray());
            $store = new MailChimp_WooCommerce_Store();
            return $store->fromArray($data);
        } catch (\Exception $e) {
            if (!$silent) throw $e;
            return false;
        }
    }

    /**
     * @param MailChimp_WooCommerce_Store $store
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Store
     * @throws Exception
     */
    public function updateStore(MailChimp_WooCommerce_Store $store, $silent = true)
    {
        try {
            $this->validateStoreSubmission($store);
            $data = $this->patch("ecommerce/stores/{$store->getId()}", $store->toArray());
            $store = new MailChimp_WooCommerce_Store();
            return $store->fromArray($data);
        } catch (\Exception $e) {
            if (!$silent) throw $e;
            return false;
        }
    }

    /**
     * @param $store_id
     * @return bool
     */
    public function deleteStore($store_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id");
            return true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param string $customer_id
     * @return MailChimp_WooCommerce_Customer|bool
     */
    public function getCustomer($store_id, $customer_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/customers/$customer_id");
            $customer = new MailChimp_WooCommerce_Customer();
            return $customer->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param MailChimp_WooCommerce_Customer $store
     * @return MailChimp_WooCommerce_Customer
     * @throws MailChimp_WooCommerce_Error
     */
    public function addCustomer(MailChimp_WooCommerce_Customer $store)
    {
        $this->validateStoreSubmission($store);
        $data = $this->post("ecommerce/stores", $store->toArray());
        $customer = new MailChimp_WooCommerce_Customer();
        return $customer->fromArray($data);
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @return array|bool
     */
    public function carts($store_id, $page = 1, $count = 10)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/carts', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Cart $cart
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Cart
     * @throws MailChimp_WooCommerce_Error
     */
    public function addCart($store_id, MailChimp_WooCommerce_Cart $cart, $silent = true)
    {
        try {
            $data = $this->post("ecommerce/stores/$store_id/carts", $cart->toArray());
            $cart = new MailChimp_WooCommerce_Cart();
            return $cart->setStoreID($store_id)->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            if (!$silent) throw $e;
            mailchimp_log('api.addCart', $e->getMessage());
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Cart $cart
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Cart
     * @throws MailChimp_WooCommerce_Error
     */
    public function updateCart($store_id, MailChimp_WooCommerce_Cart $cart, $silent = true)
    {
        try {
            $data = $this->patch("ecommerce/stores/$store_id/carts/{$cart->getId()}", $cart->toArrayForUpdate());
            $cart = new MailChimp_WooCommerce_Cart();
            return $cart->setStoreID($store_id)->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            if (!$silent) throw $e;
            mailchimp_log('api.updateCart', $e->getMessage());
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $id
     * @return bool|MailChimp_WooCommerce_Cart
     */
    public function getCart($store_id, $id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/carts/$id");
            $cart = new MailChimp_WooCommerce_Cart();
            return $cart->setStoreID($store_id)->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $id
     * @return bool
     */
    public function deleteCartByID($store_id, $id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/carts/$id");
            return true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Customer $customer
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Customer
     * @throws MailChimp_WooCommerce_Error
     */
    public function updateCustomer($store_id, MailChimp_WooCommerce_Customer $customer, $silent = true)
    {
        try {
            $this->validateStoreSubmission($customer);
            $data = $this->patch("ecommerce/stores/$store_id/customers/{$customer->getId()}", $customer->toArray());
            $customer = new MailChimp_WooCommerce_Customer();
            return $customer->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            if (!$silent) throw $e;
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $customer_id
     * @return bool
     */
    public function deleteCustomer($store_id, $customer_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/customers/$customer_id");
            return true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Order $order
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Order
     * @throws Exception
     */
    public function addStoreOrder($store_id, MailChimp_WooCommerce_Order $order, $silent = true)
    {
        try {
            if (!$this->validateStoreSubmission($order)) {
                return false;
            }
            $data = $this->post("ecommerce/stores/$store_id/orders", $order->toArray());
            update_option('mailchimp-woocommerce-resource-last-updated', time());
            $order = new MailChimp_WooCommerce_Order();
            return $order->fromArray($data);
        } catch (\Exception $e) {
            if (!$silent) throw $e;
            mailchimp_log('api.add_order.error', $e->getMessage(), array('submission' => $order->toArray()));
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Order $order
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Order
     * @throws Exception
     */
    public function updateStoreOrder($store_id, MailChimp_WooCommerce_Order $order, $silent = true)
    {
        try {
            if (!$this->validateStoreSubmission($order)) {
                return false;
            }
            $id = $order->getId();
            $data = $this->patch("ecommerce/stores/$store_id/orders/$id", $order->toArray());
            $order = new MailChimp_WooCommerce_Order();
            return $order->fromArray($data);
        } catch (\Exception $e) {
            if (!$silent) throw $e;
            mailchimp_log('api.update_order.error', $e->getMessage(), array('submission' => $order->toArray()));
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $order_id
     * @return MailChimp_WooCommerce_Order|bool
     */
    public function getStoreOrder($store_id, $order_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/orders/$order_id");
            $order = new MailChimp_WooCommerce_Order();
            return $order->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $order_id
     * @return bool
     */
    public function deleteStoreOrder($store_id, $order_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/orders/$order_id");
            return true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $product_id
     * @return MailChimp_WooCommerce_Product|bool
     */
    public function getStoreProduct($store_id, $product_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/products/$product_id");
            $product = new MailChimp_WooCommerce_Product();
            return $product->fromArray($data);
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @return array|bool
     */
    public function products($store_id, $page = 1, $count = 10)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/products', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @param MailChimp_WooCommerce_Product $product
     * @param bool $silent
     * @return bool|MailChimp_WooCommerce_Product
     * @throws Exception
     */
    public function addStoreProduct($store_id, MailChimp_WooCommerce_Product $product, $silent = true)
    {
        try {
            $this->validateStoreSubmission($product);
            $data = $this->post("ecommerce/stores/$store_id/products", $product->toArray());
            update_option('mailchimp-woocommerce-resource-last-updated', time());
            $product = new MailChimp_WooCommerce_Product();
            return $product->fromArray($data);
        } catch (\Exception $e) {
            if (!$silent) throw $e;
            mailchimp_log('api.add_product.error', $e->getMessage(), array('submission' => $product->toArray()));
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $product_id
     * @return bool
     */
    public function deleteStoreProduct($store_id, $product_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/products/$product_id");
            return true;
        } catch (MailChimp_WooCommerce_Error $e) {
            return false;
        }
    }

    /**
     * @param MailChimp_WooCommerce_Store|MailChimp_WooCommerce_Order|MailChimp_WooCommerce_Product|MailChimp_WooCommerce_Customer $target
     * @return bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function validateStoreSubmission($target)
    {
        if ($target instanceof MailChimp_WooCommerce_Order) {
            return $this->validateStoreOrder($target);
        }
        return true;
    }

    /**
     * @param MailChimp_WooCommerce_Order $order
     * @return bool
     */
    protected function validateStoreOrder(MailChimp_WooCommerce_Order $order)
    {
        if (mailchimp_string_contains($order->getCustomer()->getEmailAddress(), array('marketplace.amazon.com'))) {
            mailchimp_log('validation.amazon', "Order #{$order->getId()} was placed through Amazon. Skipping!");
            return false;
        }
        return true;
    }

    /**
     * @param $url
     * @param null $params
     * @return array|bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function delete($url, $params = null)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('DELETE', $url, $params);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param null $params
     * @return array|bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function get($url, $params = null)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('GET', $url, $params);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|mixed|null|object
     * @throws Exception
     * @throws MailChimp_WooCommerce_Error
     */
    protected function patch($url, $body)
    {
        // process the patch request the normal way
        $curl = curl_init();

        $options = $this->applyCurlOptions('PATCH', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function post($url, $body)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('POST', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function put($url, $body)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('PUT', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param string $extra
     * @param null|array $params
     * @return string
     */
    protected function url($extra = '', $params = null)
    {
        $url = "https://{$this->data_center}.api.mailchimp.com/{$this->version}/";

        if (!empty($extra)) {
            $url .= $extra;
        }

        if (!empty($params)) {
            $url .= '?'.(is_array($params) ? http_build_query($params) : $params);
        }

        return $url;
    }

    /**
     * @param $method
     * @param $url
     * @param $body
     * @return array|WP_Error
     */
    protected function sendWithHttpClient($method, $url, $body)
    {
        return _wp_http_get_object()->request($this->url($url), array(
            'method' => strtoupper($method),
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('mailchimp:'.$this->api_key),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
        ));
    }

    /**
     * @param $method
     * @param $url
     * @param array $params
     * @return array
     */
    protected function applyCurlOptions($method, $url, $params = array())
    {
        $env = mailchimp_environment_variables();

        return array(
            CURLOPT_USERPWD => "mailchimp:{$this->api_key}",
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_URL => $this->url($url, $params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => array(
                'content-type: application/json',
                "user-agent: MailChimp for WooCommerce/{$env->version}; WordPress/{$env->wp_version}",
            )
        );
    }

    /**
     * @param $curl
     * @return array|mixed|null|object
     * @throws Exception
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_ServerError
     */
    protected function processCurlResponse($curl)
    {
        $response = curl_exec($curl);

        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            throw new MailChimp_WooCommerce_Error('CURL error :: '.$err, '500');
        }

        $data = json_decode($response, true);

        if (empty($info) || ($info['http_code'] >= 200 && $info['http_code'] <= 400)) {
            if (is_array($data)) {
                try {
                    $this->checkForErrors($data);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            return $data;
        }

        if ($info['http_code'] >= 400 && $info['http_code'] <= 500) {
            throw new MailChimp_WooCommerce_Error($data['title'] .' :: '.$data['detail'], $data['status']);
        }

        if ($info['http_code'] >= 500) {
            throw new MailChimp_WooCommerce_ServerError($data['detail'], $data['status']);
        }

        return null;
    }

    /**
     * @param array $data
     * @return bool
     * @throws MailChimp_WooCommerce_Error
     */
    protected function checkForErrors(array $data)
    {
        // if we have an array of error data push it into a message
        if (isset($data['errors'])) {
            $message = '';
            foreach ($data['errors'] as $error) {
                $message .= '<p>'.$error['field'].': '.$error['message'].'</p>';
            }
            throw new MailChimp_WooCommerce_Error($message, $data['status']);
        }

        // make sure the response is correct from the data in the response array
        if (isset($data['status']) && $data['status'] >= 400) {
            throw new MailChimp_WooCommerce_Error($data['detail'], $data['status']);
        }

        return false;
    }
}
