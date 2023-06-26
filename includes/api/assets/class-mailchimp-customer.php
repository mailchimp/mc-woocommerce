<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:16 PM
 */
class MailChimp_WooCommerce_Customer {

	protected $id            = null;
	protected $email_address = null;
	protected $opt_in_status = null;
	protected $company       = null;
	protected $first_name    = null;
	protected $last_name     = null;
	protected $orders_count  = null;
	protected $total_spent   = null;
	protected $address;
	protected $marketing_status_updated_at = null;
	protected $requires_double_optin       = false;
	protected $original_subscriber_status  = null;
	protected $wordpress_user              = null;

	/**
	 * @return array
	 */
	public function getValidation() {
		return array(
			'id'            => 'required',
			'email_address' => 'required|email',
			'opt_in_status' => 'required|string',
			'company'       => 'string',
			'first_name'    => 'string',
			'last_name'     => 'string',
            // 'orders_count' => 'integer',
			// 'total_spent' => 'integer',
		);
	}

	/**
	 * @return null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param null $id
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getEmailAddress() {
		return $this->email_address;
	}

	/**
	 * @param null $email_address
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setEmailAddress( $email_address ) {
		$this->email_address = $email_address;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getOptInStatus() {
		return $this->opt_in_status;
	}

	/**
	 * @return DateTime|false|mixed|null
	 */
    public function getOptInStatusTime() {
		if ($this->marketing_status_updated_at) {
			return $this->marketing_status_updated_at;
		}

		if (($user = $this->getWordpressUser())) {
			return $this->marketing_status_updated_at = mailchimp_get_marketing_status_updated_at($user->ID);
		}
        return null;
    }

	/**
	 * @return string
	 */
	public function getOptInStatusTimeAsString()
	{
		if (($date = $this->getOptInStatusTime())) {
			return $date->format('D, M j, Y g:i A');
		}
		return '';
	}


    /**
	 * @param null $opt_in_status
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setOptInStatus( $opt_in_status ) {
        if ( is_bool( $opt_in_status ) ) {
            $this->opt_in_status = $opt_in_status;
        } else {
            $this->opt_in_status = $opt_in_status === '1';
        }
		return $this;
	}

    /**
	 * @return null
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * @param null $company
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setCompany( $company ) {
		$this->company = $company;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getFirstName() {
		return $this->first_name;
	}

	/**
	 * @param null $first_name
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setFirstName( $first_name ) {
		$this->first_name = $first_name;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLastName() {
		return $this->last_name;
	}

	/**
	 * @param null $last_name
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setLastName( $last_name ) {
		$this->last_name = $last_name;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getOrdersCount() {
		return $this->orders_count;
	}

	/**
	 * @param null $orders_count
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setOrdersCount( $orders_count ) {
		$this->orders_count = $orders_count;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getTotalSpent() {
		return $this->total_spent;
	}

	/**
	 * @param null $total_spent
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setTotalSpent( $total_spent ) {
		$this->total_spent = $total_spent;

		return $this;
	}

	/**
	 * @return MailChimp_WooCommerce_Address
	 */
	public function getAddress() {
		if ( empty( $this->address ) ) {
			$this->address = new MailChimp_WooCommerce_Address();
		}
		return $this->address;
	}

	/**
	 * @param MailChimp_WooCommerce_Address $address
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function setAddress( MailChimp_WooCommerce_Address $address ) {
		$this->address = $address;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function requiresDoubleOptIn() {
		return $this->requires_double_optin;
	}

	/**
	 * @param $bool
	 * @return $this
	 */
	public function requireDoubleOptIn( $bool ) {
		$this->requires_double_optin = (bool) $bool;

		if ( $this->requires_double_optin ) {
			if ( is_null( $this->original_subscriber_status ) ) {
				$this->original_subscriber_status = $this->opt_in_status;
			}
			$this->opt_in_status = false;
		}

		return $this;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function wasSubscribedOnOrder( $id ) {
		// we are saving the post meta for subscribers on each order... so if they have subscribed on checkout
        $order           = wc_get_order($id);
		$subscriber_meta = $order->get_meta('mailchimp_woocommerce_is_subscribed');

		$subscribed      = $subscriber_meta === '' ? false : $subscriber_meta;

		return $this->original_subscriber_status = $subscribed;
	}

	/**
	 * @return null|bool
	 */
	public function getOriginalSubscriberStatus() {
		return $this->original_subscriber_status;
	}

	/**
	 * @return array
	 */
	public function getMergeFields() {
		return array(
			'FNAME' => trim( $this->getFirstName() ),
			'LNAME' => trim( $this->getLastName() ),
		);
	}

	/**
	 * @param $user
	 * @return $this
	 */
	public function setWordpressUser( $user ) {
		if ( $user instanceof WP_User ) {
			$this->wordpress_user = $user;
		}
		return $this;
	}

	/**
	 * @return null|WP_User
	 */
	public function getWordpressUser() {
		return $this->wordpress_user;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		$address = $this->getAddress()->toArray();

		return mailchimp_array_remove_empty(
			array(
				'id'            => (string) $this->getId(),
				'email_address' => (string) $this->getEmailAddress(),
				'opt_in_status' => $this->getOptInStatus(),
                'marketing_status_updated_at' => $this->getOptInStatusTimeAsString(),
                'company'       => (string) $this->getCompany(),
                'first_name'    => (string) $this->getFirstName(),
				'last_name'     => (string) $this->getLastName(),
				// 'orders_count' => (int) $this->getOrdersCount(),
				// 'total_spent' => floatval(number_format($this->getTotalSpent(), 2, '.', '')),
				'address'       => ( empty( $address ) ? null : $address ),
			)
		);
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_Customer
	 */
	public function fromArray( array $data ) {
		$singles = array(
			'id',
			'email_address',
			'opt_in_status',
			'company',
			'first_name',
			'last_name',
			'orders_count',
			'total_spent',
		);

		foreach ( $singles as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}

		if ( array_key_exists( 'address', $data ) && is_array( $data['address'] ) ) {
			$address       = new MailChimp_WooCommerce_Address();
			$this->address = $address->fromArray( $data['address'] );
		}

		return $this;
	}
}
