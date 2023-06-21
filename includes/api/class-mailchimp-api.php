<?php

/**
 * Class MailChimp_WooCommerce_MailChimpApi
 */
class MailChimp_WooCommerce_MailChimpApi {

	protected $version     = '3.0';
	protected $data_center = 'us2';
	protected $api_key     = null;
	protected $auth_type   = 'key';

	/** @var null|MailChimp_WooCommerce_MailChimpApi */
	protected static $instance = null;

	/**
	 * @return null|MailChimp_WooCommerce_MailChimpApi
	 */
	public static function getInstance() {
		return static::$instance;
	}

	/**
	 * @param $api_key
	 *
	 * @return MailChimp_WooCommerce_MailChimpApi
	 */
	public static function constructInstance( $api_key ) {
		return static::$instance = new MailChimp_WooCommerce_MailChimpApi( $api_key );
	}

	/**
	 * MailChimp_WooCommerce_MailChimpApi constructor.
	 *
	 * @param null $api_key
	 */
	public function __construct( $api_key = null ) {
		if ( ! empty( $api_key ) ) {
			$this->setApiKey( $api_key );
		}
	}

	/**
	 * @param $key
	 *
	 * @return $this
	 */
	public function setApiKey( $key ) {
		$parts = str_getcsv( $key, '-' );

		if ( count( $parts ) == 2 ) {
			$this->data_center = $parts[1];
		}

		$this->api_key = $parts[0];

		return $this;
	}

	/**
	 * @param $dc
	 *
	 * @return $this
	 */
	public function setDataCenter( $dc ) {
		$this->data_center = $dc;

		return $this;
	}

	/**
	 * @param $version
	 *
	 * @return $this
	 */
	public function setVersion( $version ) {
		$this->version = $version;

		return $this;
	}

	/**
	 * @param false $return_profile
	 * @param false $throw_error
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function ping( $return_profile = false, $throw_error = false ) {
		try {
			$profile = $this->get( '/' );
			return $return_profile ? $profile : true;
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw_error ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getProfile() {
		return $this->get( '/' );
	}

	/**
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getAuthorizedApps() {
		return $this->get( 'authorized-apps' );
	}

	/**
	 * @param $id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getAuthorizedAppDetails( $id ) {
		return $this->get( "authorized-apps/$id" );
	}

	/**
	 * @param $client_id
	 * @param $client_secret
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function linkAuthorizedApp( $client_id, $client_secret ) {
		return $this->post(
			'authorized-apps',
			array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
			)
		);
	}

	/**
	 * @param $list_id
	 * @param $email
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function member( $list_id, $email ) {
		$hash = md5( strtolower( trim( $email ) ) );
		return $this->get( "lists/$list_id/members/$hash", array() );
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @return array|bool
	 * @throws \Throwable
	 */
	public function activity($list_id, $email)
	{
		$hash = md5(strtolower(trim($email)));
		return $this->get("lists/{$list_id}/members/{$hash}/activity");
	}

	/**
	 * @param $list_id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function members( $list_id ) {
		return $this->get( "lists/$list_id/members" );
	}

	/**
	 * @param $list_id
	 * @param $email
	 *
	 * @return bool
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function deleteMember( $list_id, $email ) {
		$hash = md5( strtolower( trim( $email ) ) );
		return (bool) $this->delete( "lists/$list_id/members/$hash", array() );
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @param bool    $subscribed
	 * @param array   $merge_fields
	 * @param array   $list_interests
	 * @param null    $language
	 * @param null    $gdpr_fields
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function subscribe( $list_id, $email, $subscribed = '1', $merge_fields = array(), $list_interests = array(), $language = null, $gdpr_fields = null ) {
		if ( $subscribed === '1' ) {
            $status = 'subscribed';
		} elseif ( $subscribed === '0' || empty($subscribed) ) {
            $status = 'transactional';
        } else {
            $status = $subscribed;
        }

		$data = $this->cleanListSubmission(
			array(
				'email_type'            => 'html',
				'email_address'         => $email,
				'status'                => $status,
				'merge_fields'          => $merge_fields,
				'interests'             => $list_interests,
				'language'              => $language,
				'marketing_permissions' => $gdpr_fields,
			)
		);

		$this->validateNaughtyListEmail( $email );

		mailchimp_debug( 'api.subscribe', "Subscribing {$email}", $data );

		try {
			return $this->post( "lists/$list_id/members?skip_merge_validation=true", $data );
		} catch ( Exception $e ) {
			// If mailchimp says is already a member lets send the update by PUT
			if ( mailchimp_string_contains( $e->getMessage(), 'is already a list member' ) ) {
				return $this->applyPutRequestOnSubscriber( $list_id, $email, $data );
			} elseif ( $data['status'] !== 'subscribed' || ! mailchimp_string_contains( $e->getMessage(), 'compliance state' ) ) {
				throw $e;
			}
			$data['status'] = 'pending';
			$result         = $this->post( "lists/$list_id/members?skip_merge_validation=true", $data );
			mailchimp_log( 'api', "{$email} was in compliance state, sending the double opt in message" );
			return $result;
		}
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @param bool    $subscribed
	 * @param array   $merge_fields
	 * @param array   $list_interests
	 * @param null    $language
	 * @param null    $gdpr_fields
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function update( $list_id, $email, $subscribed = '1', $merge_fields = array(), $list_interests = array(), $language = null, $gdpr_fields = null ) {
		$hash = md5( strtolower( trim( $email ) ) );

		if ( $subscribed === '1' ) {
			$status = 'subscribed';
		} elseif ( $subscribed === '0' ) {
			$status = 'transactional';
		} elseif ( $subscribed === null ) {
			$status = 'cleaned';
		} else {
			$status = $subscribed;
		}

		$data = $this->cleanListSubmission(
			array(
				'email_address'         => $email,
				'status'                => $status,
				'merge_fields'          => $merge_fields,
				'interests'             => $list_interests,
				'language'              => $language,
				'marketing_permissions' => $gdpr_fields,
			)
		);

		$this->validateNaughtyListEmail( $email );

		mailchimp_debug( 'api.update_member', "Updating {$email}", $data );

		try {
			return $this->patch( "lists/$list_id/members/$hash?skip_merge_validation=true", $data );
		} catch ( Exception $e ) {

			// If mailchimp says is already a member lets send the update by PUT
			if ( mailchimp_string_contains( $e->getMessage(), 'is already a list member' ) ) {
				return $this->applyPutRequestOnSubscriber( $list_id, $email, $data );
			} elseif ( $data['status'] !== 'subscribed' || ! mailchimp_string_contains( $e->getMessage(), 'compliance state' ) ) {
				throw $e;
			}

			$data['status'] = 'pending';
			$result         = $this->patch( "lists/$list_id/members/$hash?skip_merge_validation=true", $data );
			mailchimp_log( 'api', "{$email} was in compliance state, sending the double opt in message" );
			return $result;

		}
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function cleanListSubmission( $data ) {
		if ( empty( $data['merge_fields'] ) ) {
			unset( $data['merge_fields'] );
		}

		if ( empty( $data['interests'] ) ) {
			unset( $data['interests'] );
		}

		if ( empty( $data['language'] ) ) {
			unset( $data['language'] );
		}

		if ( empty( $data['marketing_permissions'] ) ) {
			unset( $data['marketing_permissions'] );
		}
		return $data;
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @param $data
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function applyPutRequestOnSubscriber( $list_id, $email, $data ) {
		try {
			$hash = md5( strtolower( trim( $email ) ) );
			mailchimp_log( 'api.update', "{$email} was already a list member sending the update by PUT" );
			return $this->put( "lists/$list_id/members/$hash?skip_merge_validation=true", $data );
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * @param $list_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getSubscribedCount( $list_id ) {
		if ( empty( $list_id ) ) {
			return 0;
		}
		return $this->get( "lists/{$list_id}/members?status=subscribed&count=1" )['total_items'];
	}

	/**
	 * @param $list_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getUnsubscribedCount( $list_id ) {
		if ( empty( $list_id ) ) {
			return 0;
		}
		return $this->get( "lists/{$list_id}/members?status=unsubscribed&count=1" )['total_items'];
	}

	/**
	 * @param $list_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getTransactionalCount( $list_id ) {
		if ( empty( $list_id ) ) {
			return 0;
		}
		return $this->get( "lists/{$list_id}/members?status=transactional&count=1" )['total_items'];
	}


	/**
	 * @param $list_id
	 * @param $email
	 * @param false   $fail_silently
	 * @param null    $order
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function updateMemberTags( $list_id, $email, $fail_silently = false, $order = null ) {
		$hash = md5( strtolower( trim( $email ) ) );
		$tags = mailchimp_get_user_tags_to_update( $email, $order );

		if ( empty( $tags ) ) {
			return false;
		}

		$data = array(
			'tags' => $tags,
		);

		mailchimp_debug( 'api.update_member_tags', "Updating {$email}", $data );

		try {
			return $this->post( "lists/$list_id/members/$hash/tags", $data );
		} catch ( Exception $e ) {
			if ( ! $fail_silently ) {
				throw $e;
			}
		}

		return false;
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @param bool    $subscribed
	 * @param array   $merge_fields
	 * @param array   $list_interests
	 * @param null    $language
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function updateOrCreate( $list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array(), $language = null ) {
		$hash = md5( strtolower( trim( $email ) ) );

		if ( $subscribed === true ) {
			$status        = 'subscribed';
			$status_if_new = 'subscribed';
		} elseif ( $subscribed === false ) {
			$status        = 'transactional';
			$status_if_new = 'pending';
		} elseif ( $subscribed === null ) {
			$status        = 'cleaned';
			$status_if_new = 'subscribed';
		} else {
			$status        = $subscribed;
			$status_if_new = 'pending';
		}

		$data = array(
			'email_address' => $email,
			'status'        => $status,
			'status_if_new' => $status_if_new,
			'merge_fields'  => $merge_fields,
			'interests'     => $list_interests,
			'language'      => $language,
		);

		if ( empty( $data['merge_fields'] ) ) {
			unset( $data['merge_fields'] );
		}

		if ( empty( $data['interests'] ) ) {
			unset( $data['interests'] );
		}

		if ( empty( $data['language'] ) ) {
			unset( $data['language'] );
		}

		$this->validateNaughtyListEmail( $email );

		mailchimp_debug( 'api.update_or_create', "Update Or Create {$email}", $data );

		return $this->put( "lists/$list_id/members/$hash", $data );
	}

	/**
	 * @param MailChimp_WooCommerce_CreateListSubmission $submission
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function createList( MailChimp_WooCommerce_CreateListSubmission $submission ) {
		return $this->post( 'lists', $submission->getSubmission() );
	}

	/**
	 * @param $list_id
	 * @param MailChimp_WooCommerce_CreateListSubmission $submission
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateList( $list_id, MailChimp_WooCommerce_CreateListSubmission $submission ) {
		return $this->patch( "lists/{$list_id}", $submission->getSubmission() );
	}

	/**
	 * @param false $as_list
	 * @param int   $count
	 *
	 * @return array|bool|object
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getLists( $as_list = false, $count = 100 ) {
		$result = $this->get( 'lists', array( 'count' => $count ) );

		if ( ! is_array( $result ) ) {
			throw new MailChimp_WooCommerce_RateLimitError( 'getting lists api failure, retry again.' );
		}

		if ( $as_list ) {
			$lists = array();
			if ( $result ) {
				$result = (object) $result;
				if ( isset( $result->lists ) && is_array( $result->lists ) ) {
					foreach ( $result->lists as $list ) {
						$list               = (object) $list;
						$lists[ $list->id ] = $list->name;
					}
				}
			}

			return $lists;
		}

		return $result;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function hasList( $id ) {
		try {
			return (bool) $this->getList( $id );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $id
	 *
	 * @return array|bool|object
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getList( $id ) {
		$result = $this->get( 'lists/' . $id );
		if ( ! is_array( $result ) ) {
			throw new MailChimp_WooCommerce_RateLimitError( 'getting list api failure, retry again.' );
		}
		return $result;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function deleteList( $id ) {
		return (bool) $this->delete( 'lists/' . $id );
	}

	/**
	 * @return array|bool|mixed|object
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getListsWithMergeFields() {
		$lists = $this->getLists( true );

		foreach ( $lists as $id => $name ) {
			$lists[ $id ] = $this->mergeFields( $id, 100 );
		}

		return $lists;
	}

	/**
	 * @param $list_id
	 * @param int     $count
	 *
	 * @return array|bool|object
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function mergeFields( $list_id, $count = 10 ) {
		$result = $this->get( "lists/$list_id/merge-fields", array( 'count' => $count ) );

		if ( ! is_array( $result ) ) {
			throw new MailChimp_WooCommerce_RateLimitError( 'getting merge field api failure, retry again.' );
		}

		return $result;
	}

	/**
	 * @param $list_id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getInterestGroups( $list_id ) {
		if ( empty( $list_id ) ) {
			return array();
		}
		return $this->get( "lists/$list_id/interest-categories" );
	}

	/**
	 * @param $list_id
	 * @param $group_id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getInterestGroupOptions( $list_id, $group_id ) {
		if ( empty( $list_id ) || empty( $group_id ) ) {
			return array();
		}
		return $this->get( "lists/$list_id/interest-categories/$group_id/interests" );
	}

	/**
	 * @param $store_id
	 * @param int           $page
	 * @param int           $count
	 * @param DateTime|null $since
	 * @param null          $campaign_id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function orders( $store_id, $page = 1, $count = 10, DateTime $since = null, $campaign_id = null ) {
		return $this->get(
			'ecommerce/stores/' . $store_id . '/orders',
			array(
				'start'  => $page,
				'count'  => $count,
				'offset' => ( $page * $count ),
				'since'  => ( $since ? $since->format( 'Y-m-d H:i:s' ) : null ),
				'cid'    => $campaign_id,
			)
		);
	}

	/**
	 * @param $store_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getOrderCount( $store_id ) {
		$data = $this->get( "ecommerce/stores/{$store_id}/orders?count=1" );
		if ( ! is_array( $data ) ) {
			return 0;
		}
		return $data['total_items'];
	}

	/**
	 * @param $store_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getProductCount( $store_id ) {
		$data = $this->get( "ecommerce/stores/{$store_id}/products?count=1" );
		if ( ! is_array( $data ) ) {
			return 0;
		}
		return $data['total_items'];
	}

	/**
	 * @param $store_id
	 *
	 * @return int|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getCustomerCount( $store_id ) {
		$data = $this->get( "ecommerce/stores/{$store_id}/customers?count=1" );
		if ( ! is_array( $data ) ) {
			return 0;
		}
		return $data['total_items'];
	}

	/**
	 * @param $store_id
	 *
	 * @return false|MailChimp_WooCommerce_Store
	 */
	public function getStoreIfAvailable( $store_id ) {
		try {
			return $this->getStore( $store_id, true );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param false    $throw
	 *
	 * @return false|MailChimp_WooCommerce_Store
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getStore( $store_id, $throw = false ) {
		try {
			$data = $this->get( "ecommerce/stores/$store_id" );
			if ( ! is_array( $data ) ) {
				throw new MailChimp_WooCommerce_RateLimitError( 'getting store api failure, retry again.' );
			}
			if ( ! isset( $data['id'] ) || ! isset( $data['name'] ) ) {
				return false;
			}
			$store = new MailChimp_WooCommerce_Store();
			return $store->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		} catch ( Exception $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $campaign_id
	 * @param bool        $throw_if_invalid
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getCampaign( $campaign_id, $throw_if_invalid = true ) {
		// don't let an empty campaign ID do anything
		if ( empty( $campaign_id ) ) {
			return false;
		}

		// if we found the campaign ID already and it's been stored in the cache, return it from the cache instead.
		if ( ( $data = get_site_transient( 'mailchimp-woocommerce-has-campaign-id-' . $campaign_id ) ) && ! empty( $data ) ) {
			return $data;
		}
		if ( get_site_transient( 'mailchimp-woocommerce-no-campaign-id-' . $campaign_id ) ) {
			return false;
		}
		try {
			$data = $this->get( "campaigns/$campaign_id" );
			delete_site_transient( 'mailchimp-woocommerce-no-campaign-id-' . $campaign_id );
			set_site_transient( 'mailchimp-woocommerce-has-campaign-id-' . $campaign_id, $data, 60 * 30 );
			return $data;
		} catch ( Exception $e ) {
			mailchimp_debug( 'campaign_get.error', 'No campaign with provided ID: ' . $campaign_id . ' :: ' . $e->getMessage() . ' :: in ' . $e->getFile() . ' :: on ' . $e->getLine() );
			set_site_transient( 'mailchimp-woocommerce-no-campaign-id-' . $campaign_id, true, 60 * 30 );

			if ( ! $throw_if_invalid ) {
				return false;
			}
			throw $e;
		}
	}

	/**
	 * @param $store_id
	 *
	 * @return array|bool|mixed|object|null
	 */
	public function checkConnectedSite( $store_id ) {
		try {
			 return $this->get( "connected-sites/{$store_id}" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 *
	 * @return array|bool|mixed|object|null
	 */
	public function connectSite( $store_id ) {
		try {
			return $this->post( "connected-sites/{$store_id}/actions/verify-script-installation", array() );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @return array|false
	 */
	public function stores() {
		try {
			$data = $this->get( 'ecommerce/stores', array( 'count' => 1000 ) );

			if ( ! isset( $data['stores'] ) || empty( $data['stores'] ) || ! is_array( $data['stores'] ) ) {
				return array();
			}

			$response = array();

			foreach ( $data['stores'] as $store_data ) {
				$store      = new MailChimp_WooCommerce_Store();
				$response[] = $store->fromArray( $store_data );
			}

			return $response;
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $is_syncing
	 *
	 * @return array|bool|mixed|object|null
	 */
	public function flagStoreSync( $store_id, $is_syncing ) {
		try {
			// pull the store to make sure we have one.
			if ( ! ( $store = $this->getStore( $store_id ) ) ) {
				return false;
			}

			// flag it as ^^^ is_syncing ^^^
			$store->flagSyncing( $is_syncing );

			// patch the store data
			return $this->patch( "ecommerce/stores/{$store_id}", $store->toArray() );

		} catch ( Exception $e ) {
			mailchimp_log( 'flag.store_sync', $e->getMessage() . ' :: in ' . $e->getFile() . ' :: on ' . $e->getLine() );
		}
		return false;
	}

	/**
	 * @param MailChimp_WooCommerce_Store $store
	 * @param bool                        $silent
	 *
	 * @return false|MailChimp_WooCommerce_Store
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addStore( MailChimp_WooCommerce_Store $store, $silent = true ) {
		try {
			$this->validateStoreSubmission( $store );
			$data  = $this->post( 'ecommerce/stores', $store->toArray() );
			$store = new MailChimp_WooCommerce_Store();
			return $store->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param MailChimp_WooCommerce_Store $store
	 * @param bool                        $silent
	 *
	 * @return false|MailChimp_WooCommerce_Store
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateStore( MailChimp_WooCommerce_Store $store, $silent = true ) {
		try {
			$this->validateStoreSubmission( $store );
			$data  = $this->patch( "ecommerce/stores/{$store->getId()}", $store->toArray() );
			$store = new MailChimp_WooCommerce_Store();
			return $store->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 *
	 * @return bool
	 */
	public function deleteStore( $store_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/$store_id" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			mailchimp_error( "delete_store {$store_id}", $e->getMessage() );
			return false;
		} catch ( Exception $e ) {
			mailchimp_error( "delete_store {$store_id}", $e->getMessage() );
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $customer_id
	 * @param false       $throw
	 *
	 * @return false|MailChimp_WooCommerce_Customer
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getCustomer( $store_id, $customer_id, $throw = false ) {
		try {
			$data = $this->get( "ecommerce/stores/$store_id/customers/$customer_id" );
			if ( ! is_array( $data ) ) {
				throw new MailChimp_WooCommerce_RateLimitError( 'getting customer api failure, retry again.' );
			}
			$customer = new MailChimp_WooCommerce_Customer();
			return $customer->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param MailChimp_WooCommerce_Customer $customer
	 *
	 * @return false|MailChimp_WooCommerce_Customer
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addCustomer( MailChimp_WooCommerce_Customer $customer ) {
		if ( ! ( $this->validateStoreSubmission( $customer ) ) ) {
			return false;
		}
		$data = $this->post( 'ecommerce/stores', $customer->toArray() );
		if ( ! is_array( $data ) ) {
			throw new MailChimp_WooCommerce_RateLimitError( 'adding customer api failure, retry again.' );
		}
		$customer = new MailChimp_WooCommerce_Customer();
		return $customer->fromArray( $data );
	}

	/**
	 * @param $store_id
	 * @param int      $page
	 * @param int      $count
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function carts( $store_id, $page = 1, $count = 10 ) {
		return $this->get(
			'ecommerce/stores/' . $store_id . '/carts',
			array(
				'start'  => $page,
				'count'  => $count,
				'offset' => ( $page * $count ),
			)
		);
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Cart $cart
	 * @param bool                       $silent
	 *
	 * @return false|MailChimp_WooCommerce_Cart
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addCart( $store_id, MailChimp_WooCommerce_Cart $cart, $silent = true ) {
		try {
			$email = $cart->getCustomer()->getEmailAddress();

			if ( mailchimp_email_is_privacy_protected( $email ) || mailchimp_email_is_amazon( $email ) ) {
				return false;
			}

			$this->validateNaughtyList( $cart->getCustomer() );

			mailchimp_debug( 'api.addCart', "Adding Cart :: {$email}", $data = $cart->toArray() );

			$data = $this->post( "ecommerce/stores/$store_id/carts", $data );
			$cart = new MailChimp_WooCommerce_Cart();
			return $cart->setStoreID( $store_id )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.addCart', $e->getMessage() );
			return false;
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Cart $cart
	 * @param bool                       $silent
	 *
	 * @return false|MailChimp_WooCommerce_Cart
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateCart( $store_id, MailChimp_WooCommerce_Cart $cart, $silent = true ) {
		try {
			$email = $cart->getCustomer()->getEmailAddress();

			if ( mailchimp_email_is_privacy_protected( $email ) || mailchimp_email_is_amazon( $email ) ) {
				return false;
			}

			$this->validateNaughtyList( $cart->getCustomer() );

			mailchimp_debug( 'api.updateCart', "Updating Cart :: {$email}", $data = $cart->toArrayForUpdate() );

			$data = $this->patch( "ecommerce/stores/$store_id/carts/{$cart->getId()}", $data );
			$cart = new MailChimp_WooCommerce_Cart();
			return $cart->setStoreID( $store_id )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.updateCart', $e->getMessage() );
			return false;
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $id
	 *
	 * @return false|MailChimp_WooCommerce_Cart
	 */
	public function getCart( $store_id, $id ) {
		try {
			$data = $this->get( "ecommerce/stores/$store_id/carts/$id" );
			$cart = new MailChimp_WooCommerce_Cart();
			return $cart->setStoreID( $store_id )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteCartByID( $store_id, $id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/$store_id/carts/$id" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Customer $customer
	 * @param bool                           $silent
	 *
	 * @return false|MailChimp_WooCommerce_Customer
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateCustomer( $store_id, MailChimp_WooCommerce_Customer $customer, $silent = true ) {
		try {
			if ( ! $this->validateStoreSubmission( $customer ) ) {
				return false;
			}
			$data     = $this->patch( "ecommerce/stores/$store_id/customers/{$customer->getId()}", $customer->toArray() );
			$customer = new MailChimp_WooCommerce_Customer();
			return $customer->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $customer_id
	 * @return bool
	 */
	public function deleteCustomer( $store_id, $customer_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/$store_id/customers/$customer_id" );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Order $order
	 * @param bool                        $silent
	 *
	 * @return false|MailChimp_WooCommerce_Order
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addStoreOrder( $store_id, MailChimp_WooCommerce_Order $order, $silent = true ) {
		try {
			if ( ! $this->validateStoreSubmission( $order ) ) {
				return false;
			}

			// submit the first one
			$data = $this->post( "ecommerce/stores/$store_id/orders", $order->toArray() );

			$email_address = $order->getCustomer()->getEmailAddress();

			// if the order is in pending status, we need to submit the order again with a paid status.
			if ( $order->shouldConfirmAndPay() && $order->getFinancialStatus() !== 'paid' ) {
				$order->setFinancialStatus( 'paid' );
				$data = $this->patch( "ecommerce/stores/{$store_id}/orders/{$order->getId()}", $order->toArray() );
			}

			// update the member tags but fail silently just in case.
			$this->updateMemberTags( mailchimp_get_list_id(), $email_address, true, $order );

			update_option( 'mailchimp-woocommerce-resource-last-updated', time() );
			$order = new MailChimp_WooCommerce_Order();
			return $order->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.add_order.error', $e->getMessage(), array( 'submission' => $order->toArray() ) );
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Order $order
	 * @param bool                        $silent
	 *
	 * @return false|MailChimp_WooCommerce_Order
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateStoreOrder( $store_id, MailChimp_WooCommerce_Order $order, $silent = true ) {
		try {
			if ( ! $this->validateStoreSubmission( $order ) ) {
				return false;
			}
			$order_id = $order->getId();
			$data     = $this->patch( "ecommerce/stores/{$store_id}/orders/{$order_id}", $order->toArray() );

			// update user tags
			$email_address = $order->getCustomer()->getEmailAddress();

			// if products list differs, we should remove the old products and add new ones
			$data_lines  = $data['lines'];
			$order_lines = $order->getLinesIds();
			foreach ( $data_lines as $line ) {
				if ( ! in_array( $line['id'], $order_lines ) ) {
					$this->deleteStoreOrderLine( $store_id, $order_id, $line['id'] );
				}
			}

			// if the order is in pending status, we need to submit the order again with a paid status.
			if ( $order->shouldConfirmAndPay() && $order->getFinancialStatus() !== 'paid' ) {
				$order->setFinancialStatus( 'paid' );
				$data = $this->patch( "ecommerce/stores/{$store_id}/orders/{$order_id}", $order->toArray() );
			}

			// update the member tags but fail silently just in case.
			$this->updateMemberTags( mailchimp_get_list_id(), $email_address, true, $order );

			$order = new MailChimp_WooCommerce_Order();
			return $order->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.update_order.error', $e->getMessage(), array( 'submission' => $order->toArray() ) );
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $order_id
	 * @param false    $throw
	 *
	 * @return false|MailChimp_WooCommerce_Order
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getStoreOrder( $store_id, $order_id, $throw = false ) {
		try {
			$data = $this->get( "ecommerce/stores/$store_id/orders/$order_id" );
			if ( ! is_array( $data ) ) {
				throw new MailChimp_WooCommerce_RateLimitError( 'getting order api failure, retry again.' );
			}
			$order = new MailChimp_WooCommerce_Order();
			return $order->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $order_id
	 *
	 * @return bool
	 */
	public function deleteStoreOrder( $store_id, $order_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/$store_id/orders/$order_id" );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $order_id
	 * @param $line_id
	 *
	 * @return bool
	 */
	public function deleteStoreOrderLine( $store_id, $order_id, $line_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/{$store_id}/orders/{$order_id}/lines/{$line_id}" );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $product_id
	 * @param false      $throw
	 *
	 * @return false|MailChimp_WooCommerce_Product
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getStoreProduct( $store_id, $product_id, $throw = false ) {
		try {
			$data = $this->get( "ecommerce/stores/$store_id/products/$product_id" );
			if ( ! is_array( $data ) ) {
				throw new MailChimp_WooCommerce_RateLimitError( 'getting product api failure, retry again.' );
			}
			$product = new MailChimp_WooCommerce_Product();
			return $product->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param int      $page
	 * @param int      $count
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function products( $store_id, $page = 1, $count = 10 ) {
		return $this->get(
			'ecommerce/stores/' . $store_id . '/products',
			array(
				'start'  => $page,
				'count'  => $count,
				'offset' => ( $page * $count ),
			)
		);
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Product $product
	 * @param bool                          $silent
	 *
	 * @return false|MailChimp_WooCommerce_Product
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addStoreProduct( $store_id, MailChimp_WooCommerce_Product $product, $silent = true ) {
		try {
			if ( ! $this->validateStoreSubmission( $product ) ) {
				return false;
			}
			$data = $this->post( "ecommerce/stores/$store_id/products", $product->toArray() );
			update_option( 'mailchimp-woocommerce-resource-last-updated', time() );
			$product = new MailChimp_WooCommerce_Product();
			return $product->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.add_product.error', $e->getMessage(), array( 'submission' => $product->toArray() ) );
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_Product $product
	 * @param bool                          $silent
	 *
	 * @return false|MailChimp_WooCommerce_Product
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	public function updateStoreProduct( $store_id, MailChimp_WooCommerce_Product $product, $silent = true ) {
		try {
			if ( ! $this->validateStoreSubmission( $product ) ) {
				return false;
			}
			$data = $this->patch( "ecommerce/stores/$store_id/products/{$product->getId()}", $product->toArray() );
			update_option( 'mailchimp-woocommerce-resource-last-updated', time() );
			$product = new MailChimp_WooCommerce_Product();
			return $product->fromArray( $data );
		} catch ( Exception $e ) {
			if ( ! $silent ) {
				throw $e;
			}
			mailchimp_log( 'api.update_product.error', $e->getMessage(), array( 'submission' => $product->toArray() ) );
			return false;
		}
	}

	/**
	 * @param MailChimp_WooCommerce_Order $order
	 *
	 * @return array
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function handleProductsMissingFromAPI( MailChimp_WooCommerce_Order $order ) {
		$missing_products = array();
		foreach ( $order->items() as $order_item ) {
			/** @var MailChimp_WooCommerce_LineItem $order_item */
			// get the line item name from the order detail just in case we need that title for the product.
			$job = new MailChimp_WooCommerce_Single_Product( $order_item->getProductId(), $order_item->getFallbackTitle() );
			if ( $missing_products[ $order_item->getId() ] = $job->createModeOnly()->fromOrderItem( $order_item )->handle() ) {
				mailchimp_debug( 'missing_products.fallback', "Product {$order_item->getId()} had to be re-pushed into Mailchimp" );
			}
		}
		return $missing_products;
	}

	/**
	 * @return bool|MailChimp_WooCommerce_Product
	 */
	public function createEmptyLineItemProductPlaceholder() {
		$product = new MailChimp_WooCommerce_Product();
		$product->setId( 'empty_line_item_placeholder' );
		$product->setTitle( 'Empty Line Item Placeholder' );
		$product->setVendor( 'deleted' );

		$variation = new MailChimp_WooCommerce_ProductVariation();
		$variation->setId( $product->getId() );
		$variation->setTitle( $product->getTitle() );
		$variation->setInventoryQuantity( 0 );
		$variation->setVisibility( 'hidden' );
		$variation->setPrice( 1 );

		$product->addVariant( $variation );

		if ( (bool) mailchimp_get_data( 'empty_line_item_placeholder', false ) ) {
			return $product;
		}

		$store_id = mailchimp_get_store_id();
		$api      = mailchimp_get_api();

		try {
			$response = $api->addStoreProduct( $store_id, $product, false );
			mailchimp_set_data( 'empty_line_item_placeholder', true );
			return $response;
		} catch ( Exception $e ) {
			return $product;
		}
	}

	/**
	 * @param $store_id
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function deleteStoreProduct( $store_id, $product_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/$store_id/products/$product_id" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_PromoRule $rule
	 * @param bool                            $throw
	 *
	 * @return false|MailChimp_WooCommerce_PromoRule
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addPromoRule( $store_id, MailChimp_WooCommerce_PromoRule $rule, $throw = true ) {
		try {
			if ( ( $response = $this->updatePromoRule( $store_id, $rule, false ) ) ) {
				return $response;
			}
			$data = $this->post( "ecommerce/stores/{$store_id}/promo-rules", $rule->toArray() );
			return ( new MailChimp_WooCommerce_PromoRule() )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_PromoRule $rule
	 * @param bool                            $throw
	 *
	 * @return false|MailChimp_WooCommerce_PromoRule
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function updatePromoRule( $store_id, MailChimp_WooCommerce_PromoRule $rule, $throw = true ) {
		try {
			$data = $this->patch( "ecommerce/stores/{$store_id}/promo-rules/{$rule->getId()}", $rule->toArray() );
			return ( new MailChimp_WooCommerce_PromoRule() )->fromArray( $data );
		} catch ( Exception $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $rule
	 *
	 * @return bool
	 */
	public function deletePromoRule( $store_id, $rule ) {
		try {
			$id = $rule instanceof MailChimp_WooCommerce_PromoRule ? $rule->getId() : $rule;
			// print_r(array('id' => $id, 'store' => $store_id));die();
			return (bool) $this->delete( "ecommerce/stores/{$store_id}/promo-rules/{$id}" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			// \Log::error("MC::deletePromoRule :: {$rule->getId()} :: {$e->getMessage()} on {$e->getLine()} in {$e->getFile()}");
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param int      $page
	 * @param int      $count
	 *
	 * @return array
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getPromoRuleIds( $store_id, $page = 1, $count = 10 ) {
		$result = $this->get(
			"ecommerce/stores/{$store_id}/promo-rules",
			array(
				'start'   => $page,
				'count'   => $count,
				'offset'  => $page > 1 ? ( ( $page - 1 ) * $count ) : 0,
				'include' => 'id',
			)
		);

		$ids = array();
		foreach ( $result['promo_rules'] as $rule ) {
			$id         = (string) $rule['id'];
			$ids[ $id ] = $id;
		}
		return $ids;
	}

	/**
	 * @param $store_id
	 * @param int      $page
	 * @param int      $count
	 * @param false    $return_original
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getPromoRules( $store_id, $page = 1, $count = 10, $return_original = false ) {
		$result = $this->get(
			"ecommerce/stores/{$store_id}/promo-rules",
			array(
				'start'  => $page,
				'count'  => $count,
				'offset' => $page > 1 ? ( ( $page - 1 ) * $count ) : 0,
			)
		);

		if ( $return_original ) {
			return $result;
		}

		$rules = array();
		foreach ( $result['promo_rules'] as $rule_data ) {
			$rule = new MailChimp_WooCommerce_PromoRule();
			$rule->fromArray( $rule_data );
			$rules[] = $rule;
		}
		return $rules;
	}

	/**
	 * @param $store_id
	 * @param $rule_id
	 * @param int      $page
	 * @param int      $count
	 * @param false    $return_original
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getPromoCodesForRule( $store_id, $rule_id, $page = 1, $count = 10, $return_original = false ) {
		$result = $this->get(
			"ecommerce/stores/{$store_id}/promo-rules/{$rule_id}/promo_codes",
			array(
				'start'  => $page,
				'count'  => $count,
				'offset' => $page > 1 ? ( ( $page - 1 ) * $count ) : 0,
			)
		);

		if ( $return_original ) {
			return $result;
		}

		$rules = array();
		foreach ( $result as $rule_data ) {
			$rule = new MailChimp_WooCommerce_PromoCode();
			$rule->fromArray( $rule_data );
			$rules[] = $rule;
		}
		return $rules;
	}

	/**
	 * @param $store_id
	 * @param $rule_id
	 * @param $code_id
	 *
	 * @return false|MailChimp_WooCommerce_PromoCode
	 */
	public function getPromoCodeForRule( $store_id, $rule_id, $code_id ) {
		try {
			$data = $this->get( "ecommerce/stores/{$store_id}/promo-rules/{$rule_id}/promo-codes/{$code_id}" );
			return ( new MailChimp_WooCommerce_PromoCode() )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $rule_id
	 *
	 * @return object
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getPromoRuleWithCodes( $store_id, $rule_id ) {
		$rule = new MailChimp_WooCommerce_PromoCode();
		$rule = $rule->fromArray( $this->get( "ecommerce/stores/{$store_id}/promo-rules/{$rule_id}" ) );
		try {
			$promo_codes = $this->getPromoCodesForRule( $store_id, $rule->getId(), 1, 100 );
			$codes       = array();
			foreach ( $promo_codes as $item ) {
				$codes[] = $item->toArray();
			}
			return (object) array(
				'rule'  => $rule->toArray(),
				'codes' => $codes,
			);
		} catch ( Exception $e ) {
			return (object) array(
				'rule'  => $rule,
				'error' => $e->getMessage(),
			);
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_PromoRule $rule
	 * @param MailChimp_WooCommerce_PromoCode $code
	 * @param bool                            $throw
	 *
	 * @return false|MailChimp_WooCommerce_PromoCode
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function addPromoCodeForRule( $store_id, MailChimp_WooCommerce_PromoRule $rule, MailChimp_WooCommerce_PromoCode $code, $throw = true ) {
		try {
			if ( ( $result = $this->updatePromoCodeForRule( $store_id, $rule, $code, false ) ) ) {
				return $result;
			}
			$data = $this->post( "ecommerce/stores/{$store_id}/promo-rules/{$rule->getId()}/promo-codes", $code->toArray() );
			return ( new MailChimp_WooCommerce_PromoCode() )->fromArray( $data );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param MailChimp_WooCommerce_PromoRule $rule
	 * @param MailChimp_WooCommerce_PromoCode $code
	 * @param bool                            $throw
	 *
	 * @return false|MailChimp_WooCommerce_PromoCode
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function updatePromoCodeForRule( $store_id, MailChimp_WooCommerce_PromoRule $rule, MailChimp_WooCommerce_PromoCode $code, $throw = true ) {
		try {
			$data = $this->patch( "ecommerce/stores/{$store_id}/promo-rules/{$rule->getId()}/promo-codes/{$code->getId()}", $code->toArray() );
			return ( new MailChimp_WooCommerce_PromoCode() )->fromArray( $data );
		} catch ( Exception $e ) {
			if ( $throw ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @param $store_id
	 * @param $rule_id
	 * @param $code_id
	 *
	 * @return bool
	 */
	public function deletePromoCodeForRule( $store_id, $rule_id, $code_id ) {
		try {
			return (bool) $this->delete( "ecommerce/stores/{$store_id}/promo-rules/{$rule_id}/promo-codes/{$code_id}" );
		} catch ( MailChimp_WooCommerce_Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $target
	 *
	 * @return bool
	 * @throws MailChimp_WooCommerce_Error
	 */
	protected function validateStoreSubmission( $target ) {
		if ( $target instanceof MailChimp_WooCommerce_Order ) {
			$this->validateNaughtyList( $target->getCustomer() );
			return $this->validateStoreOrder( $target );
		} elseif ( $target instanceof MailChimp_WooCommerce_Customer ) {
			$this->validateNaughtyList( $target );
			return $this->validateStoreCustomer( $target );
		}
		return true;
	}

	/**
	 * @param MailChimp_WooCommerce_Order $order
	 *
	 * @return bool
	 */
	protected function validateStoreOrder( MailChimp_WooCommerce_Order $order ) {
		if ( ! $this->validateStoreCustomer( $order->getCustomer() ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @param MailChimp_WooCommerce_Customer $customer
	 *
	 * @return bool
	 */
	protected function validateStoreCustomer( MailChimp_WooCommerce_Customer $customer ) {
		$email = $customer->getEmailAddress();

		if ( ! is_email( $email ) || mailchimp_email_is_amazon( $email ) || mailchimp_email_is_privacy_protected( $email ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $list_id
	 * @param int     $minutes
	 *
	 * @return array|mixed
	 */
	public function getCachedGDPRFields( $list_id, $minutes = 5 ) {
		$transient  = "mailchimp-woocommerce-gdpr-fields.{$list_id}";
		$GDPRfields = get_site_transient( $transient );

		// only return the values if it's a false - or an array
		if ( is_array( $GDPRfields ) ) {
			return $GDPRfields;
		}

		try {
			$GDPRfields = $this->getGDPRFields( $list_id );
			set_site_transient( $transient, $GDPRfields, 60 * $minutes );
		} catch ( Exception $e ) {
			$GDPRfields = array();
		}

		return $GDPRfields;
	}

	/**
	 * @param $list_id
	 *
	 * @return array|mixed
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getGDPRFields( $list_id ) {
		$one_member = $this->get( "lists/$list_id/members?fields=members.marketing_permissions&count=1" );
		$fields     = array();

		if ( is_array( $one_member ) &&
			isset( $one_member['members'] ) &&
			isset( $one_member['members'][0] ) &&
			isset( $one_member['members'][0]['marketing_permissions'] ) ) {
			$fields = $one_member['members'][0]['marketing_permissions'];
		}

		return $fields;
	}

	/**
	 * @param $list_id
	 * @param $email
	 * @param int     $minutes
	 *
	 * @return mixed|string|null
	 */
	public function getCachedSubscriberStatusForAdminProfileView( $list_id, $email, $minutes = 5 ) {
		if ( ! is_email( $email ) || ! mailchimp_is_configured() ) {
			return null;
		}
		$email_hash = md5( strtolower( trim( $email ) ) );
		$transient  = "mailchimp-woocommerce-subscribed.{$list_id}.{$email_hash}";
		$status     = get_site_transient( $transient );

		if ( ! empty( $status ) ) {
			return $status;
		}

		try {
			$member = mailchimp_get_api()->member( $list_id, $email );
			$status = $member['status'];
		} catch ( Exception $e ) {
			$status = 'not_found';
		}

		set_site_transient( $transient, $status, 60 * $minutes );

		return $status;
	}

	/**
	 * @param false $list_id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getWebHooks( $list_id = false ) {
		if ( empty( $list_id ) ) {
			$list_id = mailchimp_get_list_id();
		}
		return $this->get( "lists/{$list_id}/webhooks", array( 'count' => 1000 ) );
	}

	/**
	 * @param $list_id
	 * @param $url
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function webHookSubscribe( $list_id, $url ) {
		return $this->post(
			"lists/{$list_id}/webhooks",
			array(
				'url'     => $url,
				'events'  => array(
					'subscribe'   => true,
					'unsubscribe' => true,
					'cleaned'     => true,
					'profile'     => false,
					'upemail'     => false,
					'campaign'    => false,
				),
				'sources' => array(
					'user'  => true,
					'admin' => true,
					'api'   => true,
				),
			)
		);
	}

	/**
	 * @param $list_id
	 * @param $url
	 *
	 * @return int
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 * @throws Throwable
	 */
	public function webHookDelete( $list_id, $url ) {
		$deleted = 0;
		$hooks   = $this->getWebHooks( $list_id );
		foreach ( $hooks['webhooks'] as $hook ) {
			$href = isset( $hook['url'] ) ? $hook['url'] : ( isset( $hook['href'] ) ? $hook['href'] : null );
			if ( $href && $href === $url || ( $href && ! empty( $url ) && mailchimp_string_contains( $href, $url ) ) ) {
				mailchimp_log( 'admin', "deleting webhook id {$hook['id']} - {$href}" );
				$this->delete( "lists/{$list_id}/webhooks/{$hook['id']}" );
				$deleted++;
			}
		}
		return $deleted;
	}

	/**
	 * @param $list_id
	 * @param $id
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function deleteWebhookByID($list_id, $id)
	{
		mailchimp_log('admin', "deleting webhook id {$id}");
		return $this->delete("lists/{$list_id}/webhooks/{$id}");
	}

	/**
	 * @param $list_id
	 * @param $url
	 *
	 * @return bool
	 * @throws Throwable
	 */
	public function hasWebhook( $list_id, $url ) {
		$hooks = $this->getWebHooks( $list_id );
		foreach ( $hooks['webhooks'] as $hook ) {
			$href = isset( $hook['url'] ) ? $hook['url'] : ( isset( $hook['href'] ) ? $hook['href'] : null );
			if ( $href && $href === $url ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param MailChimp_WooCommerce_Customer $customer
	 *
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 */
	public function validateNaughtyList( MailChimp_WooCommerce_Customer $customer ) {
		$this->validateNaughtyListEmail( $customer->getEmailAddress() );
		$this->validateNaughtyListNames( $customer->getFirstName(), $customer->getLastName() );
		return false;
	}

	/**
	 * @param $email
	 *
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 */
	public function validateNaughtyListEmail( $email ) {
		if ( ! empty( $email ) && mailchimp_string_contains( $email, $this->getNaughtyList() ) ) {
			$this->reportSpamToTower( $email );
			throw new MailChimp_WooCommerce_Error( "Email [{$email}] has been blocked due to spam reports." );
		}
		return false;
	}

	/**
	 * @return array|mixed|object
	 */
	public function getNaughtyList() {
		try {
			$domains = mailchimp_get_transient( 'naughty_list_domains' );
			if ( is_array( $domains ) && isset( $domains['value'] ) ) {
				return $domains['value'];
			}
			$domains_response = wp_remote_get( 'https://tower.vextras.com/naughty-domains' );
			$domains          = json_decode( $domains_response['body'], true );
			mailchimp_set_transient( 'naughty_list_domains', $domains, 1440 );
			return $domains;
		} catch ( Throwable $e ) {
			$domains = array();
			mailchimp_set_transient( 'naughty_list_domains', $domains, 300 );
			return $domains;
		}
	}

	/**
	 * @return bool
	 */
	private function allowedToSubmitSpam() {
		// check to see if we've already set the transient.
		$status = mailchimp_get_transient( 'tower' );

		// if we've got it - just return it now.
		if ( ! empty( $status ) ) {
			return $status === 'green';
		}

		// call the API to see if we need to block traffic or not
		// this only impacts reporting spam metrics, does not impact local site blocking
		$response = wp_remote_get( 'https://tower.vextras.com/api/traffic' );
		$body     = json_decode( $response['body'] );
		$status   = $body ? $body->status : 'red';

		// set this for 5 minutes.
		mailchimp_set_transient( 'tower', $status, 120 );

		return $status === 'green';
	}

	/**
	 * @param $email
	 *
	 * @return array|mixed|object|null
	 */
	private function reportSpamToTower( $email ) {
		try {
			if ( ! $this->allowedToSubmitSpam() ) {
				return null;
			}

			$payload  = array(
				'headers' => array(
					'Content-type'     => 'application/json',
					'Accept'           => 'application/json',
					'X-Store-Platform' => 'woocommerce',
					'X-List-Id'        => mailchimp_get_list_id(),
					'X-Store-Key'      => base64_encode( mailchimp_get_store_id() . ':' . mailchimp_get_api_key() ),
				),
				'body'    => json_encode(
					array(
						'store_id' => mailchimp_get_store_id(),
						'list_id'  => mailchimp_get_list_id(),
						'domain'   => site_url(),
						'email'    => $email,
					)
				),
				'timeout' => 30,
			);
			$response = wp_remote_post( 'https://tower.vextras.com/admin-api/woocommerce/report-spam', $payload );
			return json_decode( $response['body'] );
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * @param $first_name
	 * @param $last_name
	 *
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 */
	public function validateNaughtyListNames( $first_name, $last_name ) {
		// add a list of naughty list customer names. Seems a little destructive though.
		$naughty_list_names = array(
			'mark mustermann',
		);
		$name               = "{$first_name} {$last_name}";
		if ( mailchimp_string_contains( strtolower( $name ), $naughty_list_names ) ) {
			throw new MailChimp_WooCommerce_Error( "Name [{$name}] has been blocked due to spam reports." );
		}
		return false;
	}

	/**
	 * @param $list_id
	 *
	 * @return array
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getAutomations( $list_id ) {
		$automations = $this->get( 'automations', array( 'limit' => 1000 ) );
		if ( ! is_array( $automations ) || ! array_key_exists( 'automations', $automations ) ) {
			return array();
		}
		$response = array();
		foreach ( $automations['automations'] as $automation ) {
			if ( $list_id === $automation['recipients']['list_id'] ) {
				$response[] = $automation;
			}
		}
		return $response;
	}

	/**
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	public function getJourneys() {
		$journeys = $this->get( 'customer-journeys/journeys', array( 'limit' => 1000 ));
		return is_array($journeys) && array_key_exists( 'journeys', $journeys) ? $journeys['journeys'] : array();
	}

	/**
	 * @param $id
	 */
	public function getJourney($id)
	{
		return $this->get("customer-journeys/journeys/{$id}");
	}

	/**
	 * @param $url
	 * @param null $params
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function delete( $url, $params = null ) {
		$curl = curl_init();

		$options = $this->applyCurlOptions( 'DELETE', $url, $params );

		curl_setopt_array( $curl, $options );

		return $this->processCurlResponse( $curl );
	}

	/**
	 * @param $url
	 * @param null $params
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function get( $url, $params = null ) {
		$curl = curl_init();

		$options = $this->applyCurlOptions( 'GET', $url, $params );

		curl_setopt_array( $curl, $options );

		return $this->processCurlResponse( $curl );
	}

	/**
	 * @param $url
	 * @param $body
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function patch( $url, $body ) {
		// process the patch request the normal way
		$curl = curl_init();

		$json = json_encode( $body );

		$options = $this->applyCurlOptions(
			'PATCH',
			$url,
			array(),
			array(
				'Expect:',
				'Content-Length: ' . strlen( $json ),
			)
		);

		$options[ CURLOPT_POSTFIELDS ] = $json;

		curl_setopt_array( $curl, $options );

		return $this->processCurlResponse( $curl );
	}

	/**
	 * @param $url
	 * @param $body
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function post( $url, $body ) {
		$curl = curl_init();

		$json = json_encode( $body );

		$options = $this->applyCurlOptions(
			'POST',
			$url,
			array(),
			array(
				'Expect:',
				'Content-Length: ' . strlen( $json ),
			)
		);

		$options[ CURLOPT_POSTFIELDS ] = $json;

		curl_setopt_array( $curl, $options );

		return $this->processCurlResponse( $curl );
	}

	/**
	 * @param $url
	 * @param $body
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function put( $url, $body ) {
		$curl = curl_init();

		$json = json_encode( $body );

		$options = $this->applyCurlOptions(
			'PUT',
			$url,
			array(),
			array(
				'Expect:',
				'Content-Length: ' . strlen( $json ),
			)
		);

		$options[ CURLOPT_POSTFIELDS ] = $json;

		curl_setopt_array( $curl, $options );

		return $this->processCurlResponse( $curl );
	}

	/**
	 * @param string $extra
	 * @param null   $params
	 *
	 * @return string
	 */
	protected function url( $extra = '', $params = null ) {
		$url = "https://{$this->data_center}.api.mailchimp.com/{$this->version}/";

		if ( ! empty( $extra ) ) {
			$url .= $extra;
		}

		if ( ! empty( $params ) ) {
			$url .= '?' . ( is_array( $params ) ? http_build_query( $params ) : $params );
		}

		return $url;
	}

	/**
	 * @param $method
	 * @param $url
	 * @param array  $params
	 * @param array  $headers
	 *
	 * @return array
	 */
	protected function applyCurlOptions( $method, $url, $params = array(), $headers = array() ) {
		$env          = mailchimp_environment_variables();
		$curl_options = array(
			CURLOPT_USERPWD        => "mailchimp:{$this->api_key}",
			CURLOPT_CUSTOMREQUEST  => strtoupper( $method ),
			CURLOPT_URL            => $this->url( $url, $params ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLINFO_HEADER_OUT    => true,
			CURLOPT_HTTPHEADER     => array_merge(
				array(
					'content-type: application/json',
					'accept: application/json',
					"user-agent: MailChimp for WooCommerce/{$env->version}; PHP/{$env->php_version}; WordPress/{$env->wp_version}; Woo/{$env->wc_version};",
				),
				$headers
			),
		);

		// automatically set the proper outbound IP address
		if ( ( $outbound_ip = mailchimp_get_outbound_ip() ) && ! in_array( $outbound_ip, mailchimp_common_loopback_ips() ) ) {
			$curl_options[ CURLOPT_INTERFACE ] = $outbound_ip;
		}

		// if we need to define a specific http version being used for curl requests, we can override this here.
		if ( defined( 'MAILCHIMP_USE_HTTP_VERSION' ) ) {
			$curl_options[ CURLOPT_HTTP_VERSION ] = MAILCHIMP_USE_HTTP_VERSION;
		}

		return $curl_options;
	}

	/**
	 * @param $curl
	 *
	 * @return array|bool|mixed|object|null
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 * @throws MailChimp_WooCommerce_ServerError
	 */
	protected function processCurlResponse( $curl ) {
		$response = curl_exec( $curl );

		$err  = curl_error( $curl );
		$info = curl_getinfo( $curl );
		curl_close( $curl );

		if ( $err ) {
			throw new MailChimp_WooCommerce_Error( 'CURL error :: ' . $err, 500 );
		}

		$data = json_decode( $response, true );

		$http_code  = ! empty( $info ) && isset( $info['http_code'] ) ? $info['http_code'] : -1;
		$called_url = ! empty( $info ) && isset( $info['url'] ) ? $info['url'] : 'none';

		// let's block these from doing anything below because the API seems to be having trouble.
		if ( $http_code <= 99 ) {
			throw new MailChimp_WooCommerce_RateLimitError( 'API is failing - try again.' );
		}

		// possibily a successful DELETE operation
		if ( $http_code == 204 ) {
			return true;
		}

		if ( $http_code >= 200 && $http_code <= 400 ) {
			if ( is_array( $data ) ) {
				try {
					$this->checkForErrors( $data );
				} catch ( Exception $e ) {
					throw $e;
				}
			}
			return $data;
		}

		$error_status = isset( $data['status'] ) && is_numeric( $data['status'] ) ?
			(int) $data['status'] : (int) $http_code;

		if ( $http_code >= 400 && $http_code <= 500 ) {
			if ( $http_code == 403 ) {
				throw new MailChimp_WooCommerce_RateLimitError();
			}
			$error_message  = isset( $data['title'] ) ? $data['title'] : '';
			$error_message .= isset( $data['detail'] ) ? $data['detail'] : '';
			throw new MailChimp_WooCommerce_Error( $error_message, $error_status );
		}

		if ( $http_code >= 500 ) {
			$error_message = isset( $data['detail'] ) ? $data['detail'] : '';
			throw new MailChimp_WooCommerce_ServerError( $error_message, $error_status );
		}

		if ( ! is_array( $data ) ) {
			mailchimp_error(
				'api.debug',
				'fallback when data is empty from API',
				array(
					'url'      => $called_url,
					'response' => $response,
				)
			);
			throw new MailChimp_WooCommerce_ServerError( 'API response could not be decoded.' );
		}

		return null;
	}

	/**
	 * @param array $data
	 *
	 * @return false
	 * @throws MailChimp_WooCommerce_Error
	 * @throws MailChimp_WooCommerce_RateLimitError
	 */
	protected function checkForErrors( array $data ) {
		// if we have an array of error data push it into a message
		if ( isset( $data['errors'] ) ) {
			$message = '';
			foreach ( $data['errors'] as $error ) {
				$message .= '<p>' . $error['field'] . ': ' . $error['message'] . '</p>';
			}
			throw new MailChimp_WooCommerce_Error( $message, (int) $data['status'] );
		}

		// make sure the response is correct from the data in the response array
		if ( isset( $data['status'] ) && is_numeric( $data['status'] ) && $data['status'] >= 400 ) {
			if ( isset( $data['http_code'] ) && $data['http_code'] == 403 ) {
				throw new MailChimp_WooCommerce_RateLimitError();
			}
			$error = isset( $data['detail'] ) ? $data['detail'] : ( 'Error code ' . $data['status'] );
			throw new MailChimp_WooCommerce_Error( $error, (int) $data['status'] );
		}

		return false;
	}
}
