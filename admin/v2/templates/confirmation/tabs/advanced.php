<?php
/**
 * Advanced tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>
<?php
$store_id = mailchimp_get_store_id();

$opt           = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce-comm.opt' );
$tower_opt     = \Mailchimp_Woocommerce_DB_Helpers::get_option( 'mailchimp-woocommerce-tower.opt' );
$admin_email   = mailchimp_get_option( 'admin_email', get_option( 'admin_email' ) );
$comm_enabled  = null !== $opt ? (string) $opt : '0';
$tower_enabled = null !== $tower_opt ? (string) $tower_opt : '0';
?>

<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_ADVANCED_TAB; ?>"/>
<div class="mc-wc-tab-content-wrapper advanced">
    <div class="mc-wc-tab-content-box has-underline">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Advanced settings', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-radio-checkbox-list">
            <div class="mc-wc-radio-checkbox-list-item">
                <div class="mc-wc-radio-checkbox-input">
                    <div class="mc-wc-checkbox">
                        <label class="mc-wc-checkbox-label fw-500">
                            <input id="tower_box_switch" type="checkbox" name="switch" value="1"<?php echo '1' === $tower_enabled ? ' checked="checked" ' : ''; ?>>
                            <?php esc_html_e( 'Enable support', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-radio-checkbox-description">
                    <?php esc_html_e( 'Remote diagnostics for the Mailchimp for WooCommerce plugin allows our development team to troubleshoot syncing issues.', 'mailchimp-for-woocommerce' ); ?></a>
                </div>
            </div>
            <div class="mc-wc-radio-checkbox-list-item">
                <div class="mc-wc-radio-checkbox-input">
                    <div class="mc-wc-checkbox">
                        <label class="mc-wc-checkbox-label fw-500">
                            <input id="comm_box_switch" type="checkbox" name="switch" value="1"<?php echo '1' === $comm_enabled ? ' checked="checked" ' : ''; ?>>
                            <?php esc_html_e( 'Opt-in to email', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-radio-checkbox-description">
                    <?php printf( esc_html__( 'Occasionally we may send you updates, articles and other news to the store’s admin email address. Choose whether or not you want to receive these messages at %s.', 'mailchimp-for-woocommerce' ), $admin_email ); ?></a>
                </div>
            </div>
        </div>
    </div>

		<div class="mc-wc-tab-content-box has-underline">
			<div class="mc-wc-tab-content-title">
				<h3><?php esc_html_e('Data resync', 'mailchimp-for-woocommerce' ); ?></h3>
			</div>
			<div class="mc-wc-tab-content-description">
				<?php esc_html_e('Trigger a resync of data from your WooCommerce store to Mailchimp. This does not affect the data on your WooCommerce account. ', 'mailchimp-for-woocommerce' ); ?>
			</div>
			<div class="mc-wc-button-disconnect">
		  	<?php wp_nonce_field( '_resync-nonce-' . $store_id, '_resync-nonce' ); ?>
				<a id="mailchimp_woocommerce_resync" class="mc-wc-btn mc-wc-btn-primary-outline tab-content-submit mc-woocommerce-resync-button" href="<?php echo esc_url(admin_url( 'admin.php?page=mailchimp-woocommerce&tab=plugin_settings&mc_action=resync' )) ?>"><?php esc_html_e('Resync now', 'mailchimp-for-woocommerce' ); ?></a>
			</div>
		</div>

    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Disconnect', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description">
            <?php esc_html_e('Disconnect and stop syncing data from your WooCommerce store to Mailchimp. This does not affect the data on your WooCommerce account. ', 'mailchimp-for-woocommerce' ); ?>
        </div>
        <div class="mc-wc-button-disconnect">
            <?php wp_nonce_field( '_disconnect-nonce-' . $store_id, '_disconnect-nonce' ); ?>
            <a id="mailchimp_woocommerce_disconnect" class="mc-wc-btn mc-wc-btn-primary-outline tab-content-submit"><?php esc_html_e('Disconnect', 'mailchimp-for-woocommerce' ); ?></a>
        </div>
    </div>
</div>