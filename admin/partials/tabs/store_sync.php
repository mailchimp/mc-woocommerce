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
<div class="sync-content-wrapper">

    <input type="hidden" name="mailchimp_active_settings_tab" value="store_sync"/>
    
    <div class="sync-stats-wrapper">
      
        <div class="box sync-stats-card products" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Products', 'mc-woocommerce');?></strong></span>
                <div class="progress-bar-wrapper">
                    <span class="mailchimp_product_count_partial">x / x</span>
                    <div class="progress-bar"></div>
                </div>
                <p id="mailchimp_product_count"><?php echo $mailchimp_total_products; ?></p>
                
            </div>
        </div>
        <div class="box sync-stats-card orders" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Orders', 'mc-woocommerce');?></strong></span>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar"></div>
                    <span class="mailchimp_order_count_partial">x / x</span>
                </span>
                </div>
                <p id="mailchimp_order_count"><?php echo $mailchimp_total_orders; ?></p>
            </div>
        </div>
    </div>

    <div class="sync-controls-wrapper">
        <div class="box sync-controls" >
            <a class="mc-woocommerce-disconnect-button">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="#3C3C3C"/>
                </svg>
                DISCONNECT
            </a>
            <p><strong><?php esc_html_e('Account Connected', 'mc-woocommerce');?></strong></p> <p id="mailchimp_account_connected"><?php echo $account_name; ?></p>
            <br/>
            <p><strong><?php esc_html_e('Audience Connected', 'mc-woocommerce');?></strong></p>
            <p id="mailchimp_list_name"><?php echo $mailchimp_list_name; ?></p>

            <div class="mc-woocommerce-last-sync">
                <?php if ($last_updated_time): ?>
                <p>
                    
                    <?php esc_html_e('Last Updated:', 'mc-woocommerce');?>
                    <i id="mailchimp_last_updated">
                        <?php echo date_i18n( __('D, M j, Y g:i A', 'mc-woocommerce'), $last_updated_time->getTimestamp())?>
                    </i>
                    <span class="spinner" style="float:none; background-size: 16px 16px; width: 16px; height: 16px; margin: 0px 10px"></span>
                </p>
                <?php endif; ?>
                
                <?php if ($sync_started_at && !$sync_completed_at): ?>
                    <p>
                        <strong><?php esc_html_e('Initial Sync:', 'mc-woocommerce');?></strong>
                        <i id="mailchimp_last_updated">
                            <?php esc_html_e('In Progress', 'mc-woocommerce');?>
                        </i>
                        <span class="spinner" style="float:none; background-size: 16px 16px; width: 16px; height: 16px; margin: 0px 10px"></span>
                    </p>
                    
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>
<?php if($mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1')): ?>
<h2 style="padding-top: 1em;"><?php esc_html_e('Advanced', 'mc-woocommerce');?></h2>
<p id="resync_data_help_text">
    <?php esc_html_e('You can resync your audience at any time without losing any of your e-commerce data.', 'mc-woocommerce');?>
</p>
<?php submit_button(__('Force Resync', 'mc-woocommerce'), 'primary mc-woocommerce-resync-button','submit', TRUE); ?>
<?php endif; ?>

<h2 style="padding-top: 1em;"><?php esc_html_e('More Information', 'mc-woocommerce'); ?></h2>
<ul>
    <li><?= sprintf(/* translators: %s - WP-CLI URL. */wp_kses( __( 'Have a larger store or having issues syncing? Consider using <a href=%s target=_blank>WP-CLI</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://github.com/mailchimp/mc-woocommerce/issues/158' ) );?></li>
    <li><?= esc_html__('Order and customer information will not sync if they contain an Amazon or generic email address.', 'mc-woocommerce');?></li>
    <li><?= sprintf(/* translators: %s - Mailchimp Support URL. */wp_kses( __( 'Need help to connect your store? Visit the Mailchimp <a href=%s target=_blank>Knowledge Base</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-woocommerce/' ) );?></li>
    <li><?= sprintf(/* translators: %s - Plugin review URL. */wp_kses( __( 'Want to tell us how we\'re doing? <a href=%s target=_blank>Leave a review on Wordpress.org</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' ) );?></li>
    <li><?= sprintf(/* translators: %s - Mailchimp Privacy Policy URL. */wp_kses( __( 'By using this plugin, Mailchimp will process customer information in accordance with their <a href=%s target=_blank>Privacy Policy</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/legal/privacy/' ) );?></li>
</ul>