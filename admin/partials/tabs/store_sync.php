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
            <?php wp_nonce_field( '_resync-nonce-'.$store_id, '_resync-nonce' ); ?>

            <a id="mailchimp_woocommerce_disconnect" class="mc-woocommerce-disconnect-button">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="#3C3C3C"/>
                    </svg>
                    <?php esc_html_e('DISCONNECT STORE', 'mailchimp-for-woocommerce');?>
            </a>
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


<?php $show_resync = $mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1'); ?>
<div class="sync-content-wrapper sync-more-wrapper">
    <div class="box box-half">
        <div class="content">
            <h3 style="padding-top: 1em;"><?php esc_html_e('Synchronization', 'mailchimp-for-woocommerce');?></h3>
            <p id="resync_data_help_text">
                <?php esc_html_e('You can resync your audience at any time without losing any of your e-commerce data.', 'mailchimp-for-woocommerce');?>
            </p>
            <?php if ($show_resync) : ?>
                <?php submit_button(__('Resync now', 'mailchimp-for-woocommerce'), 'primary mc-woocommerce-resync-button','submit', TRUE); ?>
            <?php else : ?>
                <?php submit_button(__('Resync now', 'mailchimp-for-woocommerce'), 'mc-woocommerce-resync-button','submit', TRUE, ['disabled' => true]); ?>
                <p class="description"><?php _e('Sync is running. Please wait until it finishes.', 'mailchimp-for-woocommerce') ?></p>
            <?php endif;?>
        </div>
    </div>
    
    <div class="box box-half">
        <div class="content">
            <h3 style="padding-top: 1em;"><?php esc_html_e('More Information', 'mailchimp-for-woocommerce'); ?></h3>
            <ul>
                <li><?= sprintf(/* translators: %s - Plugin review URL. */wp_kses( __( 'Is this plugin helping your e-commerce business? <a href=%s target=_blank>Please leave us a ★★★★★ review!</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' ) );?></li>
                <li><?= sprintf(/* translators: %s - WP-CLI URL. */wp_kses( __( 'Have a larger store or having issues syncing? Consider using <a href=%s target=_blank>WP-CLI</a> or browse documentation around common <a href=%s target=_blank>caching problems</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://github.com/mailchimp/mc-woocommerce/wiki/Advanced-Queue-Setup-In-CLI-mode' ), esc_url( 'https://github.com/mailchimp/mc-woocommerce/wiki/Using-Caches' ) );?></li>
                <li><?= esc_html__('Order and customer information will not sync if they contain an Amazon or generic email address.', 'mailchimp-for-woocommerce');?></li>
                <li><?= sprintf(/* translators: %s - Mailchimp Support URL. */wp_kses( __( 'Need help? Visit <a href=%s target=_blank>Mailchimp support</a> or <a id=%s href=%s>send us an email.</a> ', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'id' => array(),  'target'=> '_blank' ) ) ), esc_url( 'https://us1.admin.mailchimp.com/support?support_key=woo_forum' ), 'mc-woocommerce-support-form-button', '#mc-woocommerce-support-form' );?></li>
                <li><?= sprintf(/* translators: %s - Mailchimp Privacy Policy URL. */wp_kses( __( 'By using this plugin, Mailchimp will process customer information in accordance with their <a href=%s target=_blank>Privacy Policy</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/legal/privacy/' ) );?></li>
            </ul>
        </div>
    </div>

</div>

<div id="mc-woocommerce-support-form" class="mc-woocommerce-modal">
    <div id="exampleModal" class="reveal-modal">
        <a href="#mc-woocommerce-support-form-button" class="close-modal"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="black"/>
            </svg>
        </a>    
        <div class="modal-header">
            <svg width="50px" height="50px" viewBox="0 0 46 49" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M34.5458 23.5193C34.8988 23.4778 35.2361 23.4759 35.5457 23.5193C35.7252 23.107 35.7568 22.397 35.5951 21.6239C35.3544 20.4741 35.029 19.7778 34.3584 19.8863C33.6859 19.9948 33.6622 20.8271 33.9028 21.9769C34.037 22.6238 34.2776 23.1761 34.5458 23.5193Z" fill="black"/>
                <path d="M28.7763 24.4284C29.2575 24.6394 29.5534 24.7795 29.6678 24.6572C29.7427 24.5803 29.719 24.4363 29.6046 24.2489C29.368 23.8624 28.8788 23.4679 28.3621 23.249C27.303 22.7934 26.0407 22.9453 25.0664 23.6454C24.745 23.8801 24.4393 24.2075 24.4826 24.4047C24.4965 24.4698 24.5458 24.5172 24.6582 24.5329C24.9225 24.5625 25.8494 24.0951 26.9164 24.03C27.6718 23.9827 28.295 24.2174 28.7763 24.4284Z" fill="black"/>
                <path d="M27.8105 24.9806C27.1852 25.0793 26.8381 25.2863 26.6172 25.4777C26.4279 25.6433 26.3115 25.8267 26.3115 25.9549C26.3115 26.0161 26.3391 26.0516 26.3589 26.0693C26.3865 26.095 26.422 26.1088 26.4614 26.1088C26.6034 26.1088 26.919 25.9826 26.919 25.9826C27.7907 25.6709 28.3647 25.7084 28.9346 25.7735C29.2502 25.809 29.3981 25.8287 29.4672 25.7202C29.4869 25.6887 29.5125 25.6216 29.4494 25.521C29.3054 25.2804 28.6723 24.8781 27.8105 24.9806Z" fill="black"/>
                <path d="M32.5975 27.0061C33.0235 27.2152 33.4909 27.1324 33.6428 26.8227C33.7946 26.5131 33.5737 26.093 33.1497 25.8839C32.7237 25.6749 32.2563 25.7577 32.1044 26.0673C31.9506 26.377 32.1734 26.7971 32.5975 27.0061Z" fill="black"/>
                <path d="M35.3306 24.6177C34.9854 24.6118 34.6995 24.9905 34.6916 25.4638C34.6837 25.9372 34.9578 26.3257 35.303 26.3317C35.6481 26.3376 35.9341 25.9589 35.942 25.4855C35.9499 25.0122 35.6757 24.6237 35.3306 24.6177Z" fill="black"/>
                <path d="M12.1324 33.1577C12.0456 33.0492 11.9056 33.0827 11.7695 33.1143C11.6749 33.136 11.5664 33.1616 11.448 33.1596C11.1936 33.1557 10.9786 33.0452 10.8583 32.8598C10.7006 32.6192 10.7104 32.2583 10.884 31.8461C10.9076 31.7909 10.9353 31.7297 10.9648 31.6607C11.241 31.0394 11.7064 30 11.1857 29.008C10.7932 28.2625 10.1542 27.797 9.38702 27.7004C8.64939 27.6077 7.89006 27.8798 7.40685 28.4143C6.64358 29.2565 6.52328 30.4044 6.6712 30.8087C6.72445 30.9566 6.80925 30.998 6.87237 31.0059C7.00254 31.0237 7.19385 30.929 7.31416 30.6055C7.32205 30.5819 7.33388 30.5464 7.34769 30.501C7.40094 30.3294 7.50152 30.0099 7.66522 29.7555C7.86245 29.4478 8.17012 29.2348 8.53105 29.1579C8.89789 29.079 9.2746 29.15 9.58819 29.3551C10.1227 29.7062 10.3298 30.361 10.101 30.9862C9.98264 31.3096 9.79133 31.9289 9.83275 32.4378C9.91756 33.4673 10.5507 33.8795 11.1206 33.9249C11.6729 33.9466 12.0594 33.6349 12.1581 33.4081C12.2133 33.274 12.164 33.1932 12.1324 33.1577Z" fill="black"/>
                <path d="M44.044 31.2761C44.0223 31.2012 43.8862 30.7002 43.6969 30.0967C43.5075 29.4932 43.3142 29.0672 43.3142 29.0672C44.0696 27.9351 44.0834 26.9233 43.9828 26.3514C43.8763 25.6414 43.5805 25.0359 42.9829 24.4107C42.3873 23.7854 41.1684 23.1445 39.4545 22.6632C39.2593 22.608 38.6123 22.4305 38.5551 22.4127C38.5512 22.3753 38.5078 20.2945 38.4684 19.3991C38.4408 18.7522 38.3836 17.7444 38.0719 16.7504C37.6992 15.4053 37.0483 14.2298 36.2377 13.4764C38.4763 11.157 39.8726 8.60091 39.8707 6.40774C39.8647 2.19102 34.6855 0.914962 28.3033 3.55781C28.2974 3.55978 26.9602 4.1278 26.9503 4.13174C26.9444 4.12582 24.5066 1.73346 24.4692 1.7019C17.1954 -4.64488 -5.55475 20.6436 1.71899 26.7853L3.30864 28.1323C2.89644 29.2013 2.73471 30.4241 2.86685 31.7396C3.03647 33.4299 3.90822 35.0511 5.32234 36.3015C6.66348 37.4908 8.42669 38.2422 10.1386 38.2402C12.9688 44.7626 19.4359 48.7643 27.0193 48.9891C35.153 49.2317 41.981 45.4134 44.8428 38.5578C45.0301 38.0765 45.825 35.909 45.825 33.9939C45.825 32.0729 44.7382 31.2761 44.044 31.2761ZM10.7638 36.41C10.5173 36.4514 10.2649 36.4691 10.0104 36.4632C7.55298 36.3981 4.90027 34.1852 4.63598 31.5621C4.34409 28.6629 5.82527 26.4322 8.44839 25.9017C8.76198 25.8386 9.14066 25.8011 9.54892 25.8228C11.0183 25.9037 13.1838 27.0318 13.6789 30.2328C14.1187 33.0689 13.4225 35.9564 10.7638 36.41ZM8.02041 24.1681C6.38736 24.4856 4.9476 25.4106 4.06797 26.6886C3.54137 26.2508 2.56115 25.4007 2.38956 25.0694C0.985306 22.4009 3.92202 17.2138 5.97516 14.285C11.0478 7.04676 18.9922 1.56581 22.6705 2.55984C23.2681 2.72945 25.2482 5.02518 25.2482 5.02518C25.2482 5.02518 21.5719 7.06451 18.1618 9.90853C13.5704 13.4468 10.0992 18.5885 8.02041 24.1681ZM33.8079 35.3252C33.8611 35.3035 33.8986 35.2424 33.8927 35.1812C33.8848 35.1063 33.8177 35.0531 33.7448 35.0609C33.7448 35.0609 29.8969 35.6309 26.26 34.2996C26.6564 33.0117 27.7096 33.4772 29.3012 33.6054C32.1709 33.777 34.7408 33.3569 36.642 32.8125C38.2889 32.3392 40.4505 31.4083 42.1309 30.0829C42.6969 31.3274 42.8981 32.6962 42.8981 32.6962C42.8981 32.6962 43.3359 32.6173 43.7028 32.8441C44.0499 33.0571 44.3024 33.5009 44.1288 34.6448C43.7758 36.7847 42.8665 38.5223 41.338 40.1198C40.4071 41.1217 39.277 41.9935 37.9852 42.6266C37.2988 42.9875 36.5671 43.2991 35.7959 43.5516C30.033 45.4331 24.1339 43.3642 22.2326 38.9207C22.0807 38.5874 21.9525 38.2363 21.852 37.8714C21.0414 34.9426 21.7297 31.43 23.8795 29.2171C23.8795 29.2171 23.8795 29.2171 23.8795 29.2151C24.0116 29.0751 24.1477 28.9094 24.1477 28.7004C24.1477 28.5248 24.0372 28.3414 23.9406 28.2112C23.1892 27.1206 20.5818 25.2607 21.1045 21.6613C21.4792 19.0757 23.7414 17.2553 25.8498 17.3637C26.0273 17.3736 26.2067 17.3834 26.3842 17.3953C27.2974 17.4485 28.0942 17.5669 28.8476 17.5984C30.1059 17.6537 31.238 17.4702 32.5792 16.3519C33.0308 15.9752 33.3937 15.6478 34.0071 15.5453C34.0722 15.5335 34.2319 15.4763 34.5534 15.492C34.8808 15.5098 35.1924 15.5985 35.4725 15.7859C36.5474 16.5018 36.6992 18.2335 36.7545 19.4997C36.786 20.2235 36.8728 21.9729 36.9044 22.4759C36.9734 23.6237 37.2751 23.7874 37.8846 23.9886C38.2278 24.101 38.5473 24.1858 39.0167 24.318C40.4387 24.7183 41.2828 25.1227 41.8153 25.6433C42.1329 25.9688 42.2808 26.3139 42.3261 26.6433C42.4938 27.8661 41.3755 29.3788 38.4171 30.7515C35.1826 32.2524 31.2577 32.6331 28.5459 32.3313C28.3388 32.3076 27.5992 32.2248 27.5952 32.2248C25.4257 31.9329 24.1891 34.7355 25.4908 36.6565C26.329 37.8951 28.6149 38.6998 30.9008 38.6998C36.1431 38.6998 40.1724 36.4613 41.6713 34.5284C41.7167 34.4712 41.7206 34.4633 41.7916 34.3568C41.8646 34.2464 41.8055 34.1852 41.7128 34.2464C40.488 35.0846 35.0484 38.4099 29.2322 37.4099C29.2322 37.4099 28.5261 37.2936 27.8792 37.0431C27.3664 36.8439 26.2935 36.3508 26.1634 35.2483C30.8514 36.6979 33.8079 35.3252 33.8079 35.3252ZM26.3704 34.4476C26.3704 34.4476 26.3724 34.4476 26.3704 34.4476C26.3724 34.4495 26.3724 34.4495 26.3724 34.4515C26.3724 34.4495 26.3724 34.4476 26.3704 34.4476ZM17.3887 14.2554C19.1914 12.1707 21.4121 10.3602 23.4002 9.34249C23.4692 9.30699 23.5422 9.38193 23.5047 9.44899C23.3469 9.73497 23.0432 10.3464 22.9466 10.8118C22.9308 10.8848 23.0097 10.9381 23.0708 10.8966C24.3074 10.0525 26.4612 9.14921 28.3486 9.03284C28.4295 9.02693 28.4689 9.13146 28.4039 9.18076C28.1159 9.40166 27.8023 9.70539 27.5735 10.0131C27.5341 10.0663 27.5716 10.1413 27.6366 10.1413C28.962 10.1511 30.8317 10.6146 32.0486 11.297C32.1315 11.3424 32.0723 11.5021 31.9796 11.4824C30.1375 11.0603 27.1199 10.7389 23.986 11.5041C21.1893 12.1865 19.0533 13.2397 17.4952 14.3738C17.4203 14.4329 17.3256 14.3304 17.3887 14.2554Z" fill="black"/>
            </svg>
            <h3><?= __('Send us a support ticket', 'mailchimp-for-woocommerce');?></h3>
            <p class="description support-form"><?= __('The best way to get in touch with us is by submitting a ticket in the form below. </br> We do our best to get back to you within 48 hours. We look forward to hear from you!', 'mailchimp-for-woocommerce');?></p>
            
        </div>

        <div id="mc-woocommerce-create-account-step-1" class="mc-woocommerce-create-account-step tab-content-wrapper" >
            <fieldset >
                <?php $user_id = get_current_user_id(); ?>

                <input id="store_id" name="store_id" type="hidden" value="<?= mailchimp_get_store_id(); ?>">
                <input id="account_id" name="account_id" type="hidden" value="<?= $account_details['account_id']?>">
                <input id="org" name="org" type="hidden" value="<?= get_bloginfo( 'name' );?>">
                
                <div class="box box-half" >
                    <label for="first_name">
                        <span> <?php esc_html_e('First name', 'mailchimp-for-woocommerce'); ?></span>
                    </label>
                    <input required type="text" id="first_name" name="first_name_edited" value="<?= $account_details['first_name']?>"/>
                </div>
                
                <div class="box box-half" >                    
                    <label for="last_name">
                        <span> <?php esc_html_e('Last name', 'mailchimp-for-woocommerce'); ?></span>
                    </label>
                    <input required type="text" id="last_name" name="last_name_edited" value="<?= $account_details['last_name']?>"/>
                </div>
                
                <div class="box" >
                    <label for="email">
                        <span> <?php esc_html_e('Email', 'mailchimp-for-woocommerce'); ?></span>
                    </label>
                    <input required type="email" id="email" name="email" value="<?= $account_details['email']?>"/>
                </div>

                <div class="box" >
                    <label for="subject">
                        <span> <?php esc_html_e('Subject', 'mailchimp-for-woocommerce'); ?></span>
                    </label>
                    <input required type="text" id="subject" name="subject"/>
                </div>

                <div class="box" >
                    <label for="message">
                        <span> <?php esc_html_e('Message', 'mailchimp-for-woocommerce'); ?></span>
                    </label>
                    <textarea required id="message" name="message"></textarea>
                </div>
                
                <div class="box">
                    <a id="mc-woocommerce-support-form-submit" class="button button-primary whitebtn tab-content-submit"><?php esc_html_e('Send', 'mailchimp-for-woocommerce'); ?></a>
                    <span class="spinner"></span>
                </div>

                <div class="box mc-woocommerce-create-account-step-error alignright" >
                    <p id ="email_error"><?= esc_html__( 'Invalid Email. Please double check.', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="first_name_error"><?= esc_html__( 'Invalid First Name. Please double check.', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="last_name_error"><?= esc_html__( 'Invalid Last Name. Please double check.', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="subject_error"><?= esc_html__( 'Invalid Subject. Please double check.', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="message_error"><?= esc_html__( 'Invalid Message. Please double check.', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="success"><?= esc_html__( 'Message sent...', 'mailchimp-for-woocommerce' ); ?></p>
                    <p id ="error"><?= esc_html__( 'Error: Message not sent...', 'mailchimp-for-woocommerce' ); ?></p>
                </div>

            </fieldset>
        </div>
        <div class="modal-footer">
            ©2001–<?= date('Y') ?> All Rights Reserved. Mailchimp® is a registered trademark of The Rocket Science Group. Cookie Preferences, Privacy, and Terms.
        </div>

    </div>
</div>