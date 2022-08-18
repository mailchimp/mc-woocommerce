<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 6/13/17
 * Time: 1:19 PM
 */
class MailChimp_WooCommerce_PromoCode {

	/**
	 * @var string
	 * @title Promo Rule Foreign ID
	 * @description A unique identifier for the promo code
	 */
	protected $id;

	/**
	 * @var string
	 * @title Code
	 * @required
	 * @description The discount code
	 */
	protected $code;

	/**
	 * @var string
	 * @title Promo Redemption Url
	 * @required
	 * @description The url that should be used in the promotion campaign. Eg. A url that applies promo code directly at checkout or a url that points to sale page. Use store url if promotion url is not available.
	 */
	protected $redemption_url;

	/**
	 * @var string
	 * @title Description
	 * @default null
	 * @description Number of times promo code has been used.
	 */
	protected $usage_count;

	/**
	 * @var boolean
	 * @title Enabled
	 * @default true
	 * @description Whether the promo code is currently enabled. ***
	 */
	protected $enabled;

	/**
	 * @var DateTime
	 * @title Start Time
	 * @default null
	 * @description The date and time when the promotion starts in ISO 8601 format
	 */
	protected $created_at_foreign;

	/**
	 * @var DateTime
	 * @title Start Time
	 * @default null
	 * @description The date and time when the promotion starts in ISO 8601 format
	 */
	protected $updated_at_foreign;

	/**
	 * @var MailChimp_WooCommerce_PromoRule|null
	 */
	protected $promo_rule;

	/**
	 * @return array
	 */
	public function getValidation() {
		return array(
			'id'                 => 'required',
			'code'               => 'required',
			'redemption_url'     => 'required',
			'usage_count'        => 'integer',
			'created_at_foreign' => 'date',
			'updated_at_foreign' => 'date',
		);
	}

	/**
	 * @param MailChimp_WooCommerce_PromoRule $promo
	 * @return MailChimp_WooCommerce_PromoCode
	 */
	public function attachPromoRule( MailChimp_WooCommerce_PromoRule $promo ) {
		$this->promo_rule = $promo;
		return $this;
	}

	/**
	 * @return MailChimp_WooCommerce_PromoRule|null
	 */
	public function getAttachedPromoRule() {
		return $this->promo_rule;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param null $id
	 * @return MailChimp_WooCommerce_PromoCode
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @param $code
	 *
	 * @return $this
	 */
	public function setCode( $code ) {
		$this->code = $code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRedemptionURL() {
		return $this->redemption_url;
	}

	/**
	 * @param $url
	 *
	 * @return $this
	 */
	public function setRedemptionURL( $url ) {
		$this->redemption_url = $url;

		return $this;
	}

	public function getUsageCount() {
		return $this->usage_count;
	}

	/**
	 * @param $count
	 *
	 * @return $this
	 */
	public function setUsageCount( $count ) {
		$this->usage_count = $count;

		return $this;
	}

	/**
	 * @param $enabled
	 *
	 * @return $this
	 */
	public function setEnabled( $enabled ) {
		$this->enabled = (bool) $enabled;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEnabled() {
		return (bool) $this->enabled;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return mailchimp_array_remove_empty(
			array(
				'id'             => (string) $this->getId(),
				'code'           => (string) $this->getCode(),
				'redemption_url' => (string) $this->getRedemptionURL(),
				'usage_count'    => $this->getUsageCount(),
				'enabled'        => $this->isEnabled(),
			)
		);
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function fromArray( array $data ) {
		$singles = array(
			'id',
			'code',
			'usage_count',
			'enabled',
			'redemption_url',
			'created_at_foreign',
			'updated_at_foreign',
		);

		foreach ( $singles as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$this->$key = $data[ $key ];
			}
		}

		return $this;
	}
}
