<?php

$store_id = mailchimp_get_store_id();

$product_count = mailchimp_get_product_count();
$order_count = mailchimp_get_order_count();
$promo_rules_count = mailchimp_count_posts('shop_coupon');

$mailchimp_total_products = $mailchimp_total_orders = $mailchimp_total_promo_rules = 0;
$mailchimp_total_subscribers = $mailchimp_total_unsubscribed = $mailchimp_total_transactional = 0;

$store_syncing = false;
$last_updated_time = get_option('mailchimp-woocommerce-resource-last-updated');
$sync_started_at = get_option('mailchimp-woocommerce-sync.started_at');
if (!empty($sync_started_at)) {
    $sync_started_at = mailchimp_date_local($sync_started_at);
} else {
    $sync_started_at = new \DateTime();
}

$sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');
if (!empty($sync_completed_at)) {
    $sync_completed_at = mailchimp_date_local($sync_completed_at);
} else {
    $sync_completed_at = false;
}

$account_name = 'n/a';
$mailchimp_list_name = 'n/a';
if (!empty($last_updated_time)) {
    $last_updated_time = mailchimp_date_local($last_updated_time);
}

// if we have a transient set to start the sync on this page view, initiate it now that the values have been saved.
if ((bool) get_site_transient('mailchimp_woocommerce_start_sync', false)) {
    MailChimp_WooCommerce_Admin::connect()->startSync();
}

if (($mailchimp_api = mailchimp_get_api()) && ($store = $mailchimp_api->getStore($store_id))) {
    $store_syncing = $store->isSyncing();
    if (($account_details = $handler->getAccountDetails())) {
        $account_name = $account_details['account_name'];
    }
    try {
        $promo_rules = $mailchimp_api->getPromoRules($store_id, 1, 1, 1);
        $mailchimp_total_promo_rules = $promo_rules['total_items'];
        if (isset($promo_rules_count['publish']) && $mailchimp_total_promo_rules > $promo_rules_count['publish']) $mailchimp_total_promo_rules = $promo_rules_count['publish'];
    } catch (\Exception $e) { $mailchimp_total_promo_rules = 0; }
    try {
        $products = $mailchimp_api->products($store_id, 1, 1);
        $mailchimp_total_products = $products['total_items'];
        if ($mailchimp_total_products > $product_count) $mailchimp_total_products = $product_count;
    } catch (\Exception $e) { $mailchimp_total_products = 0; }
    try {
        $orders = $mailchimp_api->orders($store_id, 1, 1);
        $mailchimp_total_orders = $orders['total_items'];
        if ($mailchimp_total_orders > $order_count) $mailchimp_total_orders = $order_count;
    } catch (\Exception $e) { $mailchimp_total_orders = 0; }
    try {
        $mailchimp_total_subscribers = $mailchimp_api->getSubscribedCount($store->getListId());
    } catch (\Exception $e) { $mailchimp_total_subscribers = 0; }
    try {
        $mailchimp_total_transactional = $mailchimp_api->getTransactionalCount($store->getListId());
    } catch (\Exception $e) { $mailchimp_total_transactional = 0; }
    try {
        $mailchimp_total_unsubscribed = $mailchimp_api->getUnsubscribedCount($store->getListId());
    } catch (\Exception $e) { $mailchimp_total_unsubscribed = 0; }
    
    $mailchimp_list_name = $handler->getListName();
}
?>
<input type="hidden" name="mailchimp_active_settings_tab" value="store_sync"/>
<h2 class="box">Plugin Settings</h2>
