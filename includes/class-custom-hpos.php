<?php 
namespace HPOS_Supported_MailChimp;
use Automattic\WooCommerce\Utilities\OrderUtil;
class custom_functions_mailchimp_hpos{ 
 public function hpos_init(){ 
  $HPOS_enabled = false;
  if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ){
   if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {	$HPOS_enabled = true; }
  }  
  /* HPOS_enabled - flag for data from db, where hpos is enabled or not */  
 }
 public function hpos_custom_get_post( $post_id ){
  self::hpos_init();  
  if (!$HPOS_enabled){
   return get_post($post_id);
  }
  else{
   return wc_get_order($post_id);
  }
 }
 public function hpos_get_custom_product( $post_id ){
  self::hpos_init();  
  if (!$HPOS_enabled){
   return get_post($post_id);
  }
  else{
   return wc_get_product($post_id);
  }
 }
 public function hpos_custom_update_order_meta( $order_id, $meta_key, $optin ){
  self::hpos_init();  
  if (!$HPOS_enabled){
   update_post_meta($order_id, $meta_key, $optin);
  }
  else{
   $order_c = wc_get_order( $order_id );
   $order_c->update_meta_data( $meta_key, $optin );
   $order_c->save();
  }
 }
 public function hpos_custom_get_type( $post_id ){
  self::hpos_init();  
  if ( !$HPOS_enabled ) {
   return get_post_type($post_id);
  }
  else{
   return OrderUtil::get_order_type( $post_id );
  }
 }
}
