<?php
/**
 * Overview tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>

<?php
if ( ! isset( $handler ) ) {
	$handler = MailChimp_WooCommerce_Admin::instance();
}

//$customer_count    = mailchimp_get_customer_count();
//$product_count     = mailchimp_get_product_count();
//$order_count       = mailchimp_get_order_count();
//$promo_rules_count = mailchimp_count_posts( 'shop_coupon' );

$mailchimp_total_customers     = 0;
$mailchimp_total_products      = 0;
$mailchimp_total_orders        = 0;
$mailchimp_total_promo_rules   = 0;
$mailchimp_total_subscribers   = 0;
$mailchimp_total_unsubscribed  = 0;
$mailchimp_total_transactional = 0;

$customer_count = mailchimp_get_customer_lookup_count();

$last_updated_time = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce-resource-last-updated' );
$sync_started_at   = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce-sync.started_at' );

if ( ! empty( $sync_started_at ) ) {
	$sync_started_at = mailchimp_date_local( $sync_started_at );
} else {
	$sync_started_at = new DateTime();
}

$sync_completed_at = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce-sync.completed_at' );

if ( ! empty( $sync_completed_at ) ) {
	$sync_completed_at = mailchimp_date_local( $sync_completed_at );
} else {
	$sync_completed_at = false;
}

if ( ! empty( $last_updated_time ) ) {
	$last_updated_time = mailchimp_date_local( $last_updated_time );
} else {
    $last_updated_time = $sync_completed_at;
}

$still_syncing = $sync_started_at && ! $sync_completed_at;
$is_done_syncing = mailchimp_is_done_syncing();

if ( $store ) {
	$store_syncing   = $store->isSyncing();
	$account_details = $handler->getAccountDetails();
    $mailchimp_list_name = $handler->getListName();
	if ( $account_details ) {
		$account_name = $account_details['account_name'];
	}
	try {
		$promo_rules                 = $mailchimp_api->getPromoRules( $store_id, 1, 1, 1 );
		$mailchimp_total_promo_rules = $promo_rules['total_items'];
	} catch ( Exception $e ) {
		$mailchimp_total_promo_rules = 0;
    }
	try {
        $mailchimp_total_products = $mailchimp_api->getProductCount($store_id);
	} catch ( Exception $e ) {
		$mailchimp_total_products = 0;
    }
	try {
		$mailchimp_total_orders = $mailchimp_api->getOrderCount($store_id);
	} catch ( Exception $e ) {
		$mailchimp_total_orders = 0;
    }
    try {
        $mailchimp_total_customers = $mailchimp_api->getCustomerCount($store_id);
        if ($mailchimp_total_customers > $customer_count) $mailchimp_total_customers = $customer_count;
    } catch (Exception $e) {

    }
    //	try {
//		$mailchimp_total_subscribers = $mailchimp_api->getSubscribedCount( $store->getListId() );
//	} catch ( Exception $e ) {
//		$mailchimp_total_subscribers = 0; }
//	try {
//		$mailchimp_total_transactional = $mailchimp_api->getTransactionalCount( $store->getListId() );
//	} catch ( Exception $e ) {
//		$mailchimp_total_transactional = 0;
//    }
}

?>

<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_OVERVIEW_TAB; ?>"/>
<div class="mc-wc-tab-content-wrapper sync">
    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title has-underline">
            <h3><?php esc_html_e('Review your data in real time', 'mailchimp-for-woocommerce' ); ?></h3>
            <div class="mc-wc-text-review">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.70949 15.1159L10.0671 13.4584L13.2857 15.0882L12.7264 11.1763L14.724 9.17877L11.4895 8.60289L9.98499 5.51241L8.39959 8.68311L5.29692 9.20058L7.27222 11.1763L6.70949 15.1159ZM5.19974 17.9999C5.03414 17.9999 4.86934 17.9487 4.73014 17.8479C4.49014 17.6735 4.36533 17.3808 4.40774 17.0864L5.15094 11.8833L2.23412 8.96581C2.02212 8.75382 1.94692 8.44183 2.03812 8.15624C2.12932 7.87065 2.37252 7.66025 2.66772 7.61146L7.06615 6.87788L9.28376 2.44281C9.42056 2.17002 9.72057 1.99003 10.0038 2.00043C10.3086 2.00203 10.5854 2.17642 10.719 2.45081L12.8422 6.81228L17.3398 7.61306C17.6334 7.66505 17.8734 7.87625 17.963 8.16024C18.0526 8.44503 17.9766 8.75542 17.7654 8.96581L14.8478 11.8833L15.5918 17.0864C15.6334 17.3824 15.5086 17.6767 15.2654 17.8503C15.023 18.0247 14.703 18.0479 14.4382 17.9135L10.0558 15.6944L5.55414 17.9167C5.44214 17.9727 5.32054 17.9999 5.19974 17.9999Z" fill="#5C5F62"/>
                </svg>
                <span>
                <?php
                    echo sprintf(
                        /* translators: %s - Plugin review URL. */
												wp_kses(
                            __( 'Enjoying this plugin? <a href=%s target=_blank class="js-mailchimp-woocommerce-send-event" data-mc-event="leave_review">Leave us a review!</a>', 'mailchimp-for-woocommerce' ),
                            array(
                                'a' => array(
                                    'href'   => array(),
                                    'target' => '_blank',
																		'class' 	=> 'js-mailchimp-woocommerce-send-event',
																		'data-mc-event' => 'leave_review'
                                ),
                            )
                        ),
                        esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' )
                    );
                ?>
                </span>
            </div>
        </div>
        <div class="mc-wc-tab-content-sync">
            <div class="mc-wc-tab-content-sync-status">
                <div class="sync-status-icon">
                    <div class="sync-status-icon-wrapper">
                        <span class="<?php if (!$is_done_syncing ) { echo "mc-wc-d-none"; } ?>">
                            <svg width="86" height="86" viewBox="0 0 86 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M73.373 17.4191C75.6269 19.351 75.8879 22.7442 73.956 24.9981L41.706 62.6231C40.7317 63.7598 39.3273 64.4387 37.8313 64.4961C36.3352 64.5536 34.883 63.9845 33.8243 62.9258L17.6993 46.8008C15.6002 44.7017 15.6002 41.2985 17.6993 39.1994C19.7984 37.1003 23.2016 37.1003 25.3007 39.1994L37.3214 51.2201L65.794 18.0021C67.7259 15.7482 71.1191 15.4872 73.373 17.4191Z" fill="#805BB9"/>
                            </svg>
                        </span>
                        <span></span>
                        <img class="sync-loader <?php if ( !$still_syncing ) { echo "mc-wc-d-none"; } ?>" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../../assets/images/3dotpurple.gif' ); ?>"/>
                    </div>
                </div>
                <div class="sync-status-detail">
                    <div class="sync-status-text">
                        <span>
                            <?php esc_html_e('Status:', 'mailchimp-for-woocommerce' ); ?>
                        </span>
                        <span>
                            <?php esc_html_e($is_done_syncing ? 'Complete' : 'Syncing', 'mailchimp-for-woocommerce' ); ?>
                        </span>
                    </div>

                    <div class="sync-status-text">
                        <span style="text-align:center;">
                            <?php esc_html_e($audience_name, 'mailchimp-for-woocommerce' ); ?>
                        </span>
                    </div>

                    <div class="sync-status-time <?php if ( !$last_updated_time ) { echo "mc-wc-d-none"; } ?>">
                        <span class="sync-status-time-date">
                            <?php esc_html_e('Last sync', 'mailchimp-for-woocommerce' ); ?>
                            <span>
                                <?php if ( $last_updated_time ) : ?>
                                    <?php echo $last_updated_time->format('m/d/y')?>
                                <?php endif; ?>
                            </span>
                            
                        </span>
                        <span class="sync-status-time-date-hour">
                            <?php esc_html_e('at', 'mailchimp-for-woocommerce' ); ?>
                            <span>
                                <?php if ( $last_updated_time ) : ?>
                                    <?php echo $last_updated_time->format('g:ia T')?>
                                <?php endif; ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mc-wc-tab-content-sync-detail">
                <div class="sync-contacts">
                    <div class="sync-number">
                        <span class="sync-number-finished">
                            <?php echo $mailchimp_total_customers; ?>
                        </span>
                    </div>
                    <div class="sync-text">
                        <span><?php esc_html_e('Contacts', 'mailchimp-for-woocommerce' ); ?></span>
                        <div class="mc-wc-tooltip">
                            <svg width="26" height="24" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12ZM13 10V15L14 16V17H10V16L11 15V12H10L11 10H13ZM13 7V9H11V7H13Z" fill="#000624"/>
                            </svg>
                            <div class="mc-wc-tooltip-text">WooCommerce customers sync to Mailchimp as new contacts.</div>
                        </div>
                    </div>
                </div>
                <div class="sync-orders">
                    <div class="sync-number">
                        <span class="sync-number-finished">
                            <?php echo $mailchimp_total_orders; ?>
                        </span>
                    </div>
                    <div class="sync-text">
                        <span><?php esc_html_e('Orders', 'mailchimp-for-woocommerce' ); ?></span>
                        <div class="mc-wc-tooltip">
                            <svg width="26" height="24" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12ZM13 10V15L14 16V17H10V16L11 15V12H10L11 10H13ZM13 7V9H11V7H13Z" fill="#000624"/>
                            </svg>
                            <div class="mc-wc-tooltip-text">WooCommerce orders sync to Mailchimp so you can segment and automate based on your contacts’ past purchase behavior.</div>
                        </div>
                    </div>
                </div>
                <div class="sync-promo-codes">
                    <div class="sync-number">
                        <span class="sync-number-finished">
                            <?php echo $mailchimp_total_promo_rules; ?>
                        </span>
                    </div>
                    <div class="sync-text">
                        <span><?php esc_html_e('Promo codes', 'mailchimp-for-woocommerce' ); ?></span>
                        <div class="mc-wc-tooltip">
                            <svg width="26" height="24" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12ZM13 10V15L14 16V17H10V16L11 15V12H10L11 10H13ZM13 7V9H11V7H13Z" fill="#000624"/>
                            </svg>
                            <div class="mc-wc-tooltip-text">WooCommerce discount codes sync to Mailchimp so you can use promo code content blocks in your Mailchimp campaigns.</div>
                        </div>
                    </div>
                </div>
                <div class="sync-products">
                <div class="sync-number">
                    <span class="sync-number-finished">
                        <?php echo $mailchimp_total_products; ?>
                    </span>
                </div>
                    <div class="sync-text">
                        <span><?php esc_html_e('Products', 'mailchimp-for-woocommerce' ); ?></span>
                        <div class="mc-wc-tooltip">
                            <svg width="26" height="24" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12ZM13 10V15L14 16V17H10V16L11 15V12H10L11 10H13ZM13 7V9H11V7H13Z" fill="#000624"/>
                            </svg>
                            <div class="mc-wc-tooltip-text">WooCommerce products sync to Mailchimp so you can use the product and product recommendation content blocks in your Mailchimp campaigns.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title has-underline">
            <h3><?php esc_html_e('Here’s what we recommend you do next...', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-blogs">
            <div class="mc-wc-tab-content-blogs-item">
                <div class="mc-wc-tab-content-blogs-detail">
                    <h4><?php esc_html_e('Add a sign-up form to your store', 'mailchimp-for-woocommerce' ); ?></h4>
                    <p>
                        
                    <?php
                        echo sprintf(
                            /* translators: %s - Plugin review URL. */
                            wp_kses(
                                __( 'Turn visitors into subscribers with a customizable pop-up form that embeds on your WooCommerce storefront – no coding needed. <a href=%s target=_blank>Automate your follow-up with a special welcome offer or discount.</a>', 'mailchimp-for-woocommerce' ),
                                array(
                                    'a' => array(
                                        'href'   => array(),
                                        'target' => '_blank',
                                    ),
                                )
                            ),
                            esc_url( 'https://mailchimp.com/features/automated-welcome-email/' )
                        );
                    ?>
                    </p>
                    <a href="https://admin.mailchimp.com/audience/forms/dashboard" data-mc-event="recommendation_1" class="mc-wc-btn mc-wc-btn-primary no-linear-gradient js-mailchimp-woocommerce-send-event" target="_blank"><?php echo __('Create a pop-up form', 'mailchimp-for-woocommerce' ); ?></a>
                </div>
                <div class="mc-wc-tab-content-blogs-image">
                    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../../assets/images/blog-image-1.png' ); ?>" alt="">
                </div>
            </div>
            <div class="mc-wc-tab-content-blogs-item">
                <div class="mc-wc-tab-content-blogs-detail">
                    <h4><?php esc_html_e('Bring customers back to their shopping carts', 'mailchimp-for-woocommerce' ); ?></h4>
                    <p>
                        <?php esc_html_e('Send targeted emails to customers who leave without completing their purchase. When you use Customer Journey Builder to automatically nudge customers, you could see up to 4x more orders than if you use bulk email.*', 'mailchimp-for-woocommerce' ); ?>
                    </p>
                    <a href="https://admin.mailchimp.com/customer-journey/explore/prebuilt?id=abandoned_cart_reminder_series" data-mc-event="recommendation_2" class="mc-wc-btn mc-wc-btn-primary no-linear-gradient js-mailchimp-woocommerce-send-event" target="_blank"><?php esc_html_e('Create abandoned cart journey', 'mailchimp-for-woocommerce' ); ?></a>
                    <span><?php esc_html_e('*Requires paid plan. Functionality and features vary by plan.', 'mailchimp-for-woocommerce' ); ?>  </span>
                </div>
                <div class="mc-wc-tab-content-blogs-image">
                    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../../assets/images/blog-image-2.png' ); ?>" alt="">
                </div>
            </div>
            <div class="mc-wc-tab-content-blogs-item">
                <div class="mc-wc-tab-content-blogs-detail">
                    <h4><?php esc_html_e('Design high-performance emails in minutes', 'mailchimp-for-woocommerce' ); ?></h4>
                    <p>
                        <?php esc_html_e('Get started with flexible templates, drag-and-drop design, and our built-in, expert advice. AI-assisted tools can help generate and optimize your content.', 'mailchimp-for-woocommerce' ); ?>
                    </p>
                    <a href="https://admin.mailchimp.com/campaigns/#/create-campaign" data-mc-event="recommendation_2" class="mc-wc-btn mc-wc-btn-primary no-linear-gradient js-mailchimp-woocommerce-send-event"  target="_blank"><?php esc_html_e('Create your first email', 'mailchimp-for-woocommerce' ); ?></a>
                </div>
                <div class="mc-wc-tab-content-blogs-image">
                    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../../assets/images/blog-image-3.png' ); ?>" alt="">
                </div>
            </div>
        </div>
    </div>
</div>