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
    MailChimp_WooCommerce_Admin::startSync();
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
<div class="sync-content-wrapper">
    <div class="sync-stats-wrapper sync-stats-store">
        <div class="box sync-stats-card promo_rules" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Coupons', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_promo_rules_count"><?php echo number_format($mailchimp_total_promo_rules); ?></span>
                <div class="progress-bar-wrapper">
                    <span class="card_count_label mailchimp_promo_rules_count_partial"></span>
                    <div class="progress-bar"></div>
                </div>
            </div>
        </div>
        <div class="box sync-stats-card products" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Products', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_product_count"><?php echo number_format($mailchimp_total_products ); ?></span>
                <div class="progress-bar-wrapper">
                    <span class="card_count_label mailchimp_product_count_partial"></span>
                    <div class="progress-bar"></div>
                </div>
            </div>
        </div>
        <div class="box sync-stats-card orders" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Orders', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_order_count"><?php echo number_format($mailchimp_total_orders); ?></span>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar"></div>
                    <span class="card_count_label mailchimp_order_count_partial"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="sync-stats-wrapper sync-stats-audience" style="margin-top: 26px;">
        <div class="box sync-stats-card subscribers" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Subscribers', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_subscriber_count"><?php echo number_format($mailchimp_total_subscribers); ?></span>
                <img class="sync-loader" src="<?php echo plugin_dir_url( __FILE__ ) . "images/3dotpurple.gif"; ?>"/>
            </div>
        </div>
        <div class="box sync-stats-card transactional" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Transactional', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_transactional_count"><?php echo number_format($mailchimp_total_transactional); ?></span>
                <img class="sync-loader" src="<?php echo plugin_dir_url( __FILE__ ) . "images/3dotpurple.gif"; ?>"/>
            </div>
        </div>
        <div class="box sync-stats-card unsubscribed" >
            <div class="sync-stats-card-content">
                <span class="card_label"><strong><?php esc_html_e('Unsubscribed', 'mailchimp-for-woocommerce');?></strong></span>
                <span class="card_count" id="mailchimp_unsubscribed_count"><?php echo number_format($mailchimp_total_unsubscribed); ?></span>
                <img class="sync-loader" src="<?php echo plugin_dir_url( __FILE__ ) . "images/3dotpurple.gif"; ?>"/>
            </div>
        </div>
    </div>

    <div class="sync-controls-wrapper">
        <div class="box sync-controls">
            <?php wp_nonce_field( '_disconnect-nonce-'.$store_id, '_disconnect-nonce' ); ?>

            <button id="mailchimp_woocommerce_disconnect" 
                    type="submit" 
                    name="mailchimp_woocommerce_disconnect_store" 
                    class="mc-woocommerce-disconnect-button" 
                    value="1">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="#3C3C3C"/>
                    </svg>
                    <?php esc_html_e('DISCONNECT STORE', 'mailchimp-for-woocommerce');?>
            </button>
            
            <p><strong><?php esc_html_e('Account Connected', 'mailchimp-for-woocommerce');?></strong></p> <p id="mailchimp_account_connected"><?php echo $account_name; ?></p>
            <br/>
            <p><strong><?php esc_html_e('Audience Connected', 'mailchimp-for-woocommerce');?></strong></p>
            <p id="mailchimp_list_name"><?php echo $mailchimp_list_name; ?></p>

            <div class="mc-woocommerce-last-sync">
                <p>
                    <?php if ($last_updated_time): ?>
                        <?php esc_html_e('Status:', 'mailchimp-for-woocommerce');?>
                        <?= mailchimp_is_done_syncing() ? esc_html_e('Sync Completed', 'mailchimp-for-woocommerce') : esc_html_e('Syncing...', 'mailchimp-for-woocommerce'); ?>
                    <?php elseif ($sync_started_at && !$sync_completed_at): ?>
                        <?php esc_html_e('Initial sync in progress', 'mailchimp-for-woocommerce');?>
                    <?php endif;?>
                </p>
                <p>
                    <?php esc_html_e('Last Updated:', 'mailchimp-for-woocommerce');?>
                    <i id="mailchimp_last_updated">
                        <?php if ($last_updated_time): ?>
                            <?php echo $last_updated_time->format( __('D, M j, Y g:i A', 'mailchimp-for-woocommerce')); ?>
                        <?php else : ?>
                        <?php esc_html_e('Starting...', 'mailchimp-for-woocommerce'); ?>
                        <?php endif;?>
                    </i>
                    <span class="spinner" style="float:none; background-size: 16px 16px; width: 16px; height: 16px; margin: 0px 10px"></span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="sync-content-wrapper sync-comm-wrapper">
<?php
$opt = get_option('mailchimp-woocommerce-comm.opt');
$admin_email = mailchimp_get_option('admin_email', get_option('admin_email'));
$comm_enabled = $opt != null ? $opt : '0';
?>
<h3>Communication</h3>
        <div class="box box-half">    
            <p>
                Occasionally we may send you information about how-to's, updates, and other news to the store's admin email address. Choose whether or not you want to receive these messages at <?php echo $admin_email; ?>.
            </p>
        </div>

        <div class="box box-half comm_box_wrapper">
            <fieldset>    
                <p>
                    <span>Messaging is currently
                        <span class="comm_box_status <?= $comm_enabled === '0' ? 'hidden' : '';?>" id="comm_box_status_1" <?php if($comm_enabled === '0') echo ' class="hidden" '; ?> > <?php esc_html_e('enabled', 'mailchimp-for-woocommerce');?></span>
                        <span class="comm_box_status <?= $comm_enabled === '1' ? 'hidden' : '';?>" id="comm_box_status_0" <?php if($comm_enabled === '1') echo ' class="hidden" '; ?>> <?php esc_html_e('disabled', 'mailchimp-for-woocommerce');?></span>
                    </span>
                    <label class="el-switch el-checkbox-green">
                        <input id="comm_box_switch" type="checkbox" name="switch" <?php if($comm_enabled === '1') echo ' checked="checked" '; ?> value="1">
                        <span class="el-switch-style"></span>
                    </label>
                    <span class="mc-comm-save" id="mc-comm-save">Saved!</span>
                </p>
            </fieldset>
        </div>
    
</div>

<?php if($mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1')): ?>
<h2 style="padding-top: 1em;"><?php esc_html_e('Advanced', 'mailchimp-for-woocommerce');?></h2>
<p id="resync_data_help_text">
    <?php esc_html_e('You can resync your audience at any time without losing any of your e-commerce data.', 'mailchimp-for-woocommerce');?>
</p>
<?php submit_button(__('Force Resync', 'mailchimp-for-woocommerce'), 'primary mc-woocommerce-resync-button','submit', TRUE); ?>
<?php endif; ?>

<h2 style="padding-top: 1em;"><?php esc_html_e('More Information', 'mailchimp-for-woocommerce'); ?></h2>
<ul>
    <li><?= sprintf(/* translators: %s - Plugin review URL. */wp_kses( __( 'Is this plugin helping your e-commerce business? <a href=%s target=_blank>Please leave us a ★★★★★ review!</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' ) );?></li>
    <li><?= sprintf(/* translators: %s - WP-CLI URL. */wp_kses( __( 'Have a larger store or having issues syncing? Consider using <a href=%s target=_blank>WP-CLI</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://github.com/mailchimp/mc-woocommerce/wiki/Advanced-Queue-Setup-In-CLI-mode' ) );?></li>
    <li><?= esc_html__('Order and customer information will not sync if they contain an Amazon or generic email address.', 'mailchimp-for-woocommerce');?></li>
    <li><?= sprintf(/* translators: %s - Mailchimp Support URL. */wp_kses( __( 'Need help? Visit Mailchimp <a href=%s target=_blank>support</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://us1.admin.mailchimp.com/support?support_key=woo_forum' ) );?></li>
    <li><?= sprintf(/* translators: %s - Mailchimp Privacy Policy URL. */wp_kses( __( 'By using this plugin, Mailchimp will process customer information in accordance with their <a href=%s target=_blank>Privacy Policy</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/legal/privacy/' ) );?></li>
</ul>