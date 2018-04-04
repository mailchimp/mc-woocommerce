<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:16 PM
 */
class MailChimp_WooCommerce_Customer
{
    protected $id = null;
    protected $email_address = null;
    protected $opt_in_status = null;
    protected $company = null;
    protected $first_name = null;
    protected $last_name = null;
    protected $orders_count = null;
    protected $total_spent = null;
    protected $address;

    /**
     * @return array
     */
    public function getValidation()
    {
        return array(
            'id' => 'required',
            'email_address' => 'required|email',
            'opt_in_status' => 'required|boolean',
            'company' => 'string',
            'first_name' => 'string',
            'last_name' => 'string',
            'orders_count' => 'integer',
            'total_spent' => 'integer',
        );
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_Customer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return null
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * @param null $email_address
     * @return MailChimp_WooCommerce_Customer
     */
    public function setEmailAddress($email_address)
    {
        $this->email_address = $email_address;

        return $this;
    }

    /**
     * @return null
     */
    public function getOptInStatus()
    {
        return $this->opt_in_status;
    }

    /**
     * @param null $opt_in_status
     * @return MailChimp_WooCommerce_Customer
     */
    public function setOptInStatus($opt_in_status)
    {
        $this->opt_in_status = $opt_in_status;

        return $this;
    }

    /**
     * @return null
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param null $company
     * @return MailChimp_WooCommerce_Customer
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return null
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param null $first_name
     * @return MailChimp_WooCommerce_Customer
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * @return null
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param null $last_name
     * @return MailChimp_WooCommerce_Customer
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * @return null
     */
    public function getOrdersCount()
    {
        return $this->orders_count;
    }

    /**
     * @param null $orders_count
     * @return MailChimp_WooCommerce_Customer
     */
    public function setOrdersCount($orders_count)
    {
        $this->orders_count = $orders_count;

        return $this;
    }

    /**
     * @return null
     */
    public function getTotalSpent()
    {
        return $this->total_spent;
    }

    /**
     * @param null $total_spent
     * @return MailChimp_WooCommerce_Customer
     */
    public function setTotalSpent($total_spent)
    {
        $this->total_spent = $total_spent;

        return $this;
    }

    /**
     * @return MailChimp_WooCommerce_Address
     */
    public function getAddress()
    {
        if (empty($this->address)) {
            $this->address = new MailChimp_WooCommerce_Address();
        }
        return $this->address;
    }

    /**
     * @param MailChimp_WooCommerce_Address $address
     * @return MailChimp_WooCommerce_Customer
     */
    public function setAddress(MailChimp_WooCommerce_Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $address = $this->getAddress()->toArray();

        return mailchimp_array_remove_empty(array(
            'id' => (string) $this->getId(),
            'email_address' => (string) $this->getEmailAddress(),
            'opt_in_status' => $this->getOptInStatus(),
            'company' => (string) $this->getCompany(),
            'first_name' => (string) $this->getFirstName(),
            'last_name' => (string) $this->getLastName(),
            'orders_count' => (int) $this->getOrdersCount(),
            'total_spent' => floatval(number_format($this->getTotalSpent(), 2)),
            'address' => (empty($address) ? null : $address),
        ));
    }

    /**
     * @param array $data
     * @return MailChimp_WooCommerce_Customer
     */
    public function fromArray(array $data)
    {
        $singles = array(
            'id', 'email_address', 'opt_in_status', 'company',
            'first_name', 'last_name', 'orders_count', 'total_spent',
        );

        foreach ($singles as $key) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }

        if (array_key_exists('address', $data) && is_array($data['address'])) {
            $address = new MailChimp_WooCommerce_Address();
            $this->address = $address->fromArray($data['address']);
        }

        return $this;
    }
}
