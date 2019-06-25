<?php
$mailchimp_total_products = $mailchimp_total_orders = 0;
$store_id = mailchimp_get_store_id();
$product_count = mailchimp_get_product_count();
$order_count = mailchimp_get_order_count();
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
    MailChimp_WooCommerce_Admin::startSync();
}

if (($mailchimp_api = mailchimp_get_api()) && ($store = $mailchimp_api->getStore($store_id))) {
    $store_syncing = $store->isSyncing();
    if (($account_details = $handler->getAccountDetails())) {
        $account_name = $account_details['account_name'];
    }
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
    $mailchimp_list_name = $handler->getListName();
}
?>

<input type="hidden" name="mailchimp_active_settings_tab" value="store_sync"/>

<h2 style="padding-top: 1em;"><?php esc_html_e('Sync Information', 'mc-woocommerce');?></h2>

<?php if ($sync_started_at && !$sync_completed_at): ?>
    <p><strong><?php esc_html_e('Initial Sync:', 'mc-woocommerce');?></strong> <i><?php esc_html_e('In Progress', 'mc-woocommerce');?></i></p>
<?php endif; ?>

<?php if ($last_updated_time): ?>
    <p>
        <strong>
            <?php esc_html_e('Last Updated:', 'mc-woocommerce');?>
        </strong>
        <i id="mailchimp_last_updated">
            <?php echo date_i18n( __('D, M j, Y g:i A', 'mc-woocommerce'), $last_updated_time->getTimestamp())?>
        </i>
        <span class="spinner" style="float:none; background-size: 16px 16px; width: 16px; height: 16px; margin: 0px 10px"></span>
    </p>
<?php endif; ?>

<p><strong><?php esc_html_e('Account Connected:', 'mc-woocommerce');?></strong> <span id="mailchimp_account_connected"><?php echo $account_name; ?></span></p>
<p><strong><?php esc_html_e('Audience Connected:', 'mc-woocommerce');?></strong> <span id="mailchimp_list_name"><?php echo $mailchimp_list_name; ?></span></p>
<p><strong><?php esc_html_e('Products Synced:', 'mc-woocommerce');?></strong> <span id="mailchimp_product_count"><?php echo $mailchimp_total_products; ?></span></p>
<p><strong><?php esc_html_e('Orders Synced:', 'mc-woocommerce');?></strong> <span id="mailchimp_order_count"><?php echo $mailchimp_total_orders; ?></span></p>

<?php if($mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1')): ?>
    <h2 style="padding-top: 1em;"><?php esc_html_e('Advanced', 'mc-woocommerce');?></h2>
    <p id="resync_data_help_text">
        <?php esc_html_e('You can resync your audience at any time without losing any of your e-commerce data.', 'mc-woocommerce');?>
    </p>
    <?php submit_button(__('Resync', 'mc-woocommerce'), 'primary','submit', TRUE); ?>
<?php endif; ?>
