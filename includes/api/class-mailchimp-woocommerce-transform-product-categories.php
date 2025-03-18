<?php

class MailChimp_WooCommerce_Transform_Product_Categories {

	/**
	 * @param int $page
	 * @param int $limit
	 *
	 * @return object
	 */
	public function compile( $page = 1, $limit = 5 ) {
		$response = (object) array(
			'endpoint' => 'product_categories',
			'page'     => $page ? $page : 1,
			'limit'    => (int) $limit,
			'count'    => 0,
			'stuffed'  => false,
			'items'    => array(),
            'has_next_page' => false
		);

		if ( ( $categories = $this->getProductCategoryIds( $page, $limit ) ) && ! empty( $categories['items'] )) {
			foreach ( $categories['items'] as $term_id ) {
				$response->items[] = $term_id;
				$response->count++;
			}

            $response->has_next_page = $categories['has_next_page'];
        }

		$response->stuffed = $response->count > 0 && (int) $response->count === (int) $limit;

		return $response;
	}

    /**
     * @param $term
     * @return Mailchimp_WooCommerce_Product_Category
     */
	public function transform($term) {
        try {
            $product_category = new Mailchimp_WooCommerce_Product_Category();

            $product_category->setId($term->term_id);
            $product_category->setName($term->name);
            $product_category->setDescription($term->description);

            if ($term->parent) {
                $product_category->setParentCollectionId($term->parent);
            }

            $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
            $image_url = wp_get_attachment_url( $thumbnail_id );

            if ($image_url) {
                $product_category->setImageUrl($image_url);
            }

            $product_category->setUpdatedAtForeign();

            if ($term_url = get_term_link($term)) {
                $product_category->setUrl($term_url);
            }

            /// we might need to add support for collections later.
            $product_category->setType('collection');

            return $product_category;
        } catch ( Exception $e ) {
            mailchimp_error('category_transformer.error', 'd', ['error' => $e->getMessage()]);

            return new Mailchimp_WooCommerce_Product_Category();
        } catch ( \Throwable $t ) {
            mailchimp_error('category_transformer.error', 'd2', ['error' => $t->getMessage()]);

            return new Mailchimp_WooCommerce_Product_Category();
        }
	}

	/**
	 * @param int $page
	 * @param int $posts
     *
	 * @return array|bool
	 */
	public function getProductCategoryIds( $page = 1, $posts = 5 ) {
		$offset = 0;

		if ( $page > 1 ) {
			$offset = ( ( $page - 1 ) * $posts );
		}

        $limit = $posts + 1;

		$params = array(
            'taxonomy'      => 'product_cat',
			'number'        => $limit,
			'offset'        => $offset,
			'orderby'       => 'id',
			'order'         => 'ASC',
			'fields'        => 'ids',
		);

		$categories = get_terms( $params );

        $has_next_page = count( $categories ) > $posts;

        if ( $has_next_page ) {
            array_pop( $categories );
        }

		if ( empty( $categories ) ) {
			sleep( 2 );
            $categories = get_terms( $params );

            $has_next_page = count( $categories ) > $posts;

            if ( $has_next_page ) {
                array_pop( $categories );
            }

            if ( empty( $categories ) ) {
				return false;
			}
		}

        return [
            'items' => $categories,
            'has_next_page' => $has_next_page,
        ];
    }
}
