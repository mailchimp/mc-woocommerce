<?php

class MailChimp_WooCommerce_Transform_Customers {

	/**
	 * @param int $page
	 * @param int $limit
	 *
	 * @return object
	 */
	public function compile( $page = 1, $limit = 5 ) {
		$response = (object) array(
			'endpoint' => 'customers',
			'page'     => $page ? $page : 1,
			'limit'    => (int) $limit,
			'count'    => 0,
			'stuffed'  => false,
			'items'    => array(),
            'has_next_page' => false,
		);

		if ( ( ( $customers = $this->getCustomersLookup( $page, $limit ) ) && ! empty( $customers['items'] ) ) ) {
			foreach ( $customers['items'] as $customer ) {
				$response->items[] = $customer;
				$response->count++;
			}

            $response->has_next_page = $customers['has_next_page'];
		}

		$response->stuffed = $response->count > 0 && (int) $response->count === (int) $limit;

		return $response;
	}

	/**
	 * @param $woo
	 * @param null    $fallback_title
	 *
	 * @return MailChimp_WooCommerce_Customer
	 * @throws Exception
	 */
	public function transform( $woo, $fallback_title = null ) {
        // TODO we may need to make it for MailChimp_WooCommerce_Rest_Api to replace code for customer
        return new MailChimp_WooCommerce_Customer();
	}

	/**
	 * @param int $page
	 * @param int $posts
	 * @return array|bool
	 */
	public function getCustomersLookup( $page = 1, $posts = 5 ) {
        global $wpdb;

        $offset = 0;

        if ( $page > 1 ) {
            $offset = ( ( $page - 1 ) * $posts );
        }

        $limit = $posts + 1;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wc_customer_lookup LIMIT %d OFFSET %d",
            $limit,
            $offset
        );

        $results = $wpdb->get_results($query);

        $has_next_page = count( $results ) > $posts;

        if ( $has_next_page ) {
            array_pop( $results );
        }

        if ( empty( $results ) ) {
            sleep( 2 );
            $results = $wpdb->get_results($query);

            if ( empty( $results ) ) {
                return false;
            }

            $has_next_page = count( $results ) > $posts;
            if ( $has_next_page ) {
                array_pop( $results );
            }
        }

        return [
            'items' => $results,
            'has_next_page' => $has_next_page,
        ];
	}
}
