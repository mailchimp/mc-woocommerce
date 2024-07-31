<?php
/**
 * Confirmation header template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>

<?php
if ( (bool) \Mailchimp_Woocommerce_DB_Helpers::get_transient( 'mailchimp_woocommerce_start_sync' ) ) {
	MailChimp_WooCommerce_Admin::connect()->startSync();
}
$store_id = mailchimp_get_store_id();
$mailchimp_api = mailchimp_get_api();
$store         = $mailchimp_api ? $mailchimp_api->getStoreIfAvailable( $store_id ) : null;
$admin_email   = mailchimp_get_option( 'admin_email', get_option( 'admin_email' ) );
?>
<div class="mc-wc-header-content confirmation">
    <div class="mc-wc-header-content-wrapper">
        <div class="mc-wc-header-content-details">
            <h2 class="mc-wc-title"><?php esc_html_e('You’re on your way!', 'mailchimp-for-woocommerce'); ?></h2>
        </div>
        <div class="mc-wc-header-content-image">
            <div class="mc-wc-image">
                <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../assets/images/Mailchimp-image.png' ); ?>" alt="<?php esc_html_e( 'Mailchimp Woocommerce', 'mailchimp-for-woocommerce' ); ?>">
            </div>
        </div>
    </div>
    <div class="mc-wc-header-content-footer">
        <div class="mc-wc-header-content-footer-wrapper">
            <p class="mc-wc-descripition"><?php printf( esc_html__('Most syncs take less than a few hours, but larger stores could take longer. We’ll send an email to %s when the sync is finished. Head to your Mailchimp dashboard to continue setup while your sync is in progress.', 'mailchimp-for-woocommerce'), $admin_email ); ?></p>
            <a href="http://admin.mailchimp.com/integrations/manage?name=woocommerce&source=partner" target="_blank" data-mc-event="continue_to_mailchimp" class="mc-wc-btn mc-wc-btn-primary js-mailchimp-woocommerce-send-event" style="padding:12px 32px;"><?php esc_html_e('Continue to Mailchimp', 'mailchimp-for-woocommerce'); ?></a>
        </div>
    </div>
</div>