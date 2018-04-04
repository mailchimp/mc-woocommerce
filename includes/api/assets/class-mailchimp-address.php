<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:22 PM
 */
class MailChimp_WooCommerce_Address
{
    protected $type;
    protected $name;
    protected $address1;
    protected $address2;
    protected $city;
    protected $province;
    protected $province_code;
    protected $postal_code;
    protected $country;
    protected $country_code;
    protected $longitude;
    protected $latitude;
    protected $phone;
    protected $company;

    /**
     * @return array
     */
    public function getValidation()
    {
        return array(
            'address1' => 'string',
            'address2' => 'string',
            'city' => 'string',
            'province' => 'string',
            'province_code' => 'string|digits:2',
            'postal_code' => 'string',
            'country' => 'string',
            'country_code' => 'string|digits:2',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
        );
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return MailChimp_WooCommerce_Address
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param mixed $address1
     * @return MailChimp_WooCommerce_Address
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param mixed $address2
     * @return MailChimp_WooCommerce_Address
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     * @return MailChimp_WooCommerce_Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param mixed $province
     * @return MailChimp_WooCommerce_Address
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvinceCode()
    {
        return $this->province_code;
    }

    /**
     * @param mixed $province_code
     * @return MailChimp_WooCommerce_Address
     */
    public function setProvinceCode($province_code)
    {
        $this->province_code = $province_code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @param mixed $postal_code
     * @return MailChimp_WooCommerce_Address
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return MailChimp_WooCommerce_Address
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @param mixed $country_code
     * @return MailChimp_WooCommerce_Address
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $longitude
     * @return MailChimp_WooCommerce_Address
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param mixed $latitude
     * @return MailChimp_WooCommerce_Address
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     * @return MailChimp_WooCommerce_Address
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     * @return MailChimp_WooCommerce_Address
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return mailchimp_array_remove_empty(array(
            'name' => (string) $this->name,
            'address1' => (string) $this->address1,
            'address2' => (string) $this->address2,
            'city' => (string) $this->city,
            'province' => (string) $this->province,
            'province_code' => (string) $this->province_code,
            'postal_code' => (string) $this->postal_code,
            'country' => (string) $this->country,
            'country_code' => (string) $this->country_code,
            'longitude' => ($this->longitude ? (int) $this->longitude : null),
            'latitude' => ($this->latitude ? (int) $this->latitude : null),
            'phone' => (string) $this->phone,
            'company' => (string) $this->company,
        ));
    }

    /**
     * @param array $data
     * @return MailChimp_WooCommerce_Address
     */
    public function fromArray(array $data)
    {
        $singles = array(
            'name', 'address1', 'address2', 'city',
            'province', 'province_code', 'postal_code',
            'country', 'country_code', 'longitude',
            'phone', 'company',
        );

        foreach ($singles as $key) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }

        return $this;
    }
}
