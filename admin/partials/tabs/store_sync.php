<?php

$mailchimp_total_products = $mailchimp_total_orders = 0;
$store_id = mailchimp_get_store_id();
$product_count = mailchimp_get_product_count();
$order_count = mailchimp_get_order_count();
$store_syncing = false;
$last_updated_time = get_option('mailchimp-woocommerce-resource-last-updated');
$account_name = 'n/a';
$mailchimp_list_name = 'n/a';

if (!empty($last_updated_time)) {
    $last_updated_time = mailchimp_date_local($last_updated_time);
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

<?php if($store_syncing): ?>
    <h2 style="padding-top: 1em;">Sync Progress</h2>
<?php endif; ?>

<?php if(!$store_syncing): ?>
    <h2 style="padding-top: 1em;">Sync Status</h2>
<?php endif; ?>

<p>
    <strong>Account Connected:</strong> <?php echo $account_name; ?>
</p>

<p>
    <strong>List Connected:</strong> <?php echo $mailchimp_list_name; ?>
</p>

<p>
    <strong>Products:</strong> <?php echo $mailchimp_total_products; ?>/<?php echo $product_count; ?>
</p>

<p>
    <strong>Orders:</strong> <?php echo $mailchimp_total_orders; ?>/<?php echo $order_count; ?>
</p>

<?php if ($last_updated_time): ?>
    <p><strong>Last Updated:</strong> <i><?php echo $last_updated_time->format('D, M j, Y g:i A'); ?></i></p>
<?php endif; ?>

<?php if($mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1')): ?>
    <h2 style="padding-top: 1em;">Advanced</h2>
    <p>
        You may sync your list again if necessary. When this is done, all ecommerce data will be reset in your MailChimp list - including products and transaction data.
    </p>
    <?php submit_button('Resync', 'primary','submit', TRUE); ?>
<?php endif; ?>
