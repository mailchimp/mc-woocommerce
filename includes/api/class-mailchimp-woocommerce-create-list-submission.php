<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 7/8/16
 * Time: 4:16 PM
 */
class MailChimp_WooCommerce_CreateListSubmission
{
    /**
     * @var array
     */
    protected $props = array();

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->props['name'] = $name;

        return $this;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setUseArchiveBar($bool)
    {
        $this->props['use_archive_bar'] = (bool) $bool;

        return $this;
    }

    /**
     * @param $reminder
     * @return $this
     */
    public function setPermissionReminder($reminder)
    {
        $this->props['permission_reminder'] = $reminder;

        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setNotifyOnSubscribe($email)
    {
        $this->props['notify_on_subscribe'] = $email;

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setNotifyOnUnSubscribe($email)
    {
        $this->props['notify_on_unsubscribe'] = $email;

        return $this;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setEmailTypeOption($bool)
    {
        $this->props['email_type_option'] = (bool) $bool;

        return $this;
    }

    /**
     * @param bool $public
     * @return $this
     */
    public function setVisibility($public = true)
    {
        $this->props['visibility'] = $public ? 'pub' : 'prv';

        return $this;
    }

     /**
     * @param bool $public
     * @return $this
     */
    public function setDoi($doi = false)
    {
        $this->props['double_optin'] = (bool) $doi;

        return $this;
    }

    /**
     * @param $name
     * @param $email
     * @param $subject
     * @param string $language
     * @return $this
     */
    public function setCampaignDefaults($name, $email, $subject, $language = 'en')
    {
        $this->props['campaign_defaults'] = array(
            'from_name' => $name,
            'from_email' => $email,
            'subject' => $subject,
            'language' => $language,
        );

        return $this;
    }

    /**
     * @param MailChimp_WooCommerce_Address $address
     * @return $this
     */
    public function setContact(MailChimp_WooCommerce_Address $address)
    {
        $data = array();

        if (($company = $address->getCompany()) && !empty($company)) {
            $data['company'] = $company;
        }

        if (($street = $address->getAddress1()) && !empty($address)) {
            $data['address1'] = $street;
        }

        if (($city = $address->getCity()) && !empty($city)) {
            $data['city'] = $city;
        }

        if (($state = $address->getProvince()) && !empty($state)) {
            $data['state'] = $state;
        }

        if (($zip = $address->getPostalCode()) && !empty($zip)) {
            $data['zip'] = $zip;
        }

        if (($country = $address->getCountry()) && !empty($country)) {
            $data['country'] = $country;
        }

        if (($phone = $address->getPhone()) && !empty($phone)) {
            $data['phone'] = $phone;
        }

        $this->props['contact'] = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubmission()
    {
        return $this->props;
    }
}
