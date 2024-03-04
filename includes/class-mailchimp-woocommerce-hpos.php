<?php
use Automattic\WooCommerce\Utilities\OrderUtil;
class MailChimp_WooCommerce_HPOS {
	/**
	 * @return bool
	 */
	public static function enabled()
	{
		/* HPOS_enabled - flag for data from db, where hpos is enabled or not */
		return class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) &&
		       OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function get_order( $post_id )
	{
		return wc_get_order($post_id);
	}

	/**
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function get_product( $post_id )
	{
		return wc_get_product($post_id);
	}

	/**
	 * @param $order_id
	 * @param $meta_key
	 * @param $optin
	 *
	 * @return void
	 */
	public static function update_order_meta( $order_id, $meta_key, $meta_value, $force_use_post = 0 )
    {
		if (!static::enabled() || $force_use_post) {
			update_post_meta($order_id, $meta_key, $meta_value);
			return;
		} else {
            $order_c = wc_get_order( $order_id );
            $order_c->update_meta_data( $meta_key, $meta_value );
            $order_c->save_meta_data();
        }
    }

	/**
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function get_type( $post_id )
	{
		return !static::enabled() ? get_post_type($post_id) : OrderUtil::get_order_type( $post_id );
	}

	/**
	 * @param $order_id
	 *
	 * @return mixed|null
	 */
	public static function get_order_for_tower($order_id)
	{
		if (($order = static::get_order($order_id))) {
			return $order;
		}
		if ((($real_id = static::get_post_id_from_order_number($order_id)))) {
			return static::get_order($real_id);
		}
		return null;
	}

	/**
	 * @param $order_number
	 *
	 * @return null|int
	 */
	public static function get_post_id_from_order_number($order_number)
	{
		if (static::enabled()) {
			$orders = wc_get_orders([
				'return'     => 'ids',
				'limit'      => 1,
				'meta_query' => [
					[
						'key'        => '_order_number',
						'value'      => $order_number,
						'comparison' => '='
					],
				],
			]);
		} else {
			$orders = get_posts([
				'numberposts' => 1,
				'meta_key'    => '_order_number',
				'meta_value'  => $order_number,
				'post_type'   => 'shop_order',
				'post_status' => 'any',
				'fields'      => 'ids',
			]);
		}

		return $orders ? current($orders) : null;
	}
}