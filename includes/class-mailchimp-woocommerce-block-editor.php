<?php

use Automattic\WooCommerce\Utilities\FeaturesUtil;

class Mailchimp_Woocommerce_Block_Editor {
	/**
	 * @return bool
	 */
	public static function enabled() {
		return class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) &&
		       FeaturesUtil::feature_is_enabled( 'product_block_editor' );
	}
}