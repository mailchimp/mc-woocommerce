<?php if (time() < 1722470400 && WC()->countries->get_base_country() === 'US'): ?>
    <?php
    $contact_limits = [500, 1500, 2500, 5000, 10000, 15000, 20000, 25000, 30000, 40000, 50000, 75000, 100000];
    $create_account_url = admin_url('admin.php?page=create-mailchimp-account');
    $promo_active = true;
    ?>
    <?php include_once 'promo-banner.php'; ?>
<?php endif; ?>

<div class="mc-wc-header-content connect-account">
    <div class="mc-wc-header-content-wrapper">
        <div class="mc-wc-header-content-details">
            <h2 class="mc-wc-title"><?php echo __('Let’s connect <br>  '.get_option('blogname').' <br> to Mailchimp', 'mailchimp-for-woocommerce'); ?></h2>
            <p class="mc-wc-description">
            <?php esc_html_e('Log in to your Mailchimp account to install the app, or create a new account to authorize and connect to WooCommerce. Setup should take about 5-10 minutes.', 'mailchimp-for-woocommerce'); ?>
            </p>
        </div>
        <div class="mc-wc-header-content-image">
            <div class="mc-wc-image">
                <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '../../assets/images/Connect.png' ); ?>" alt="<?php esc_html_e( 'Account Connect', 'mailchimp-for-woocommerce' ); ?>">
            </div>
        </div>
    </div>
    <div class="mc-wc-btn-acctions-wrapper">
        <?php include_once __DIR__ . '/button-actions.php'; ?>
        <div class="box">
            <input type="hidden" name="mailchimp_woocommerce_wizard_on" value=1>
            <input type="hidden" name="mailchimp_woocommerce_settings_hidden" value="Y">
            <?php
            // skip this once during the oauth success post.
            if ( ! $clicked_sync_button && !mailchimp_get_transient('oauth_success', false) ) {
                settings_fields( $this->plugin_name );
                do_settings_sections( $this->plugin_name );
                include __DIR__ . '/../tabs/notices.php';
            }
            ?>
        </div>
    </div>
</div>