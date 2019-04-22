<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api_key';
$is_mailchimp_post = isset($_POST['mailchimp_woocommerce_settings_hidden']) && $_POST['mailchimp_woocommerce_settings_hidden'] === 'Y';

$handler = MailChimp_WooCommerce_Admin::connect();

//Grab all options for this particular tab we're viewing.
$options = get_option($this->plugin_name, array());

if (!isset($_GET['tab']) && isset($options['active_tab'])) {
    $active_tab = $options['active_tab'];
}

$show_sync_tab = isset($_GET['resync']) ? $_GET['resync'] === '1' : false;

// if we have a transient set to start the sync on this page view, initiate it now that the values have been saved.
if (!$show_sync_tab && (bool) get_site_transient('mailchimp_woocommerce_start_sync', false)) {
    $show_sync_tab = true;
    $active_tab = 'sync';
}

$show_campaign_defaults = true;
$has_valid_api_key = false;
$allow_new_list = true;
$clicked_sync_button = $is_mailchimp_post && $active_tab == 'sync';
$has_api_error = isset($options['api_ping_error']) && !empty($options['api_ping_error']) ? $options['api_ping_error'] : null;

if (isset($options['mailchimp_api_key'])) {
    try {
        if ($handler->hasValidApiKey(null, true)) {
            $has_valid_api_key = true;

            // if we don't have a valid api key we need to redirect back to the 'api_key' tab.
            if (($mailchimp_lists = $handler->getMailChimpLists()) && is_array($mailchimp_lists)) {
                $show_campaign_defaults = true;
                $allow_new_list = false;
            }

            // only display this button if the data is not syncing and we have a valid api key
            if ((bool) $this->getData('sync.started_at', false)) {
                $show_sync_tab = true;
            }
        }
    } catch (\Exception $e) {
        $has_api_error = $e->getMessage().' on '.$e->getLine().' in '.$e->getFile();
    }
}

if (mailchimp_should_init_rest_queue() && !get_site_transient('http_worker_queue_listen')) {
    mailchimp_call_rest_api_queue_manually();
}

?>

<style>
    #sync-status-message strong {
        font-weight:inherit;
    }
    #log-viewer {
        background: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 5px 20px;
    }
    #log-viewer-select {
        padding: 10px 0 8px;
        line-height: 28px;
    }
    #log-viewer pre {
        font-family: monospace;
        white-space: pre-wrap;
    }
    user agent stylesheet
    pre, xmp, plaintext, listing {
        display: block;
        font-family: monospace;
        white-space: pre;
        margin: 1em 0px;
    }
</style>



<?php if (!defined('PHP_VERSION_ID') || (PHP_VERSION_ID < 70000)): ?>
    <div data-dismissible="notice-php-version" class="error notice notice-error is-dismissible">
        <p><?php esc_html_e('Mailchimp says: Please upgrade your PHP version to a minimum of 7.0', 'mailchimp-woocommerce'); ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($has_api_error)): ?>
    <div data-dismissible="notice-api-error" class="error notice notice-error is-dismissible">
        <p><?php esc_html_e("Mailchimp says: API Request Error - ".$has_api_error, 'mailchimp-woocommerce'); ?></p>
    </div>
<?php endif; ?>

<?php settings_errors(); ?>

<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2><?= __('Mailchimp for Woocommerce Settings', 'mailchimp-woocommerce');?></h2>

    <h2 class="nav-tab-wrapper">
        <a href="?page=mailchimp-woocommerce&tab=api_key" class="nav-tab <?php echo $active_tab == 'api_key' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Connect', 'mailchimp-woocommerce');?></a>
        <?php if($has_valid_api_key): ?>
        <a href="?page=mailchimp-woocommerce&tab=store_info" class="nav-tab <?php echo $active_tab == 'store_info' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Store Settings', 'mailchimp-woocommerce');?></a>
        <?php if ($handler->hasValidStoreInfo()) : ?>
        <?php if($show_campaign_defaults): ?>
        <a href="?page=mailchimp-woocommerce&tab=campaign_defaults" class="nav-tab <?php echo $active_tab == 'campaign_defaults' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Audience Defaults', 'mailchimp-woocommerce');?></a>
        <?php endif; ?>
        <?php if($handler->hasValidCampaignDefaults()): ?>
            <a href="?page=mailchimp-woocommerce&tab=newsletter_settings" class="nav-tab <?php echo $active_tab == 'newsletter_settings' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Audience Settings', 'mailchimp-woocommerce');?></a>
        <?php endif; ?>
        <?php if($show_sync_tab): ?>
            <a href="?page=mailchimp-woocommerce&tab=sync" class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Sync', 'mailchimp-woocommerce');?></a>
            <a href="?page=mailchimp-woocommerce&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Logs', 'mailchimp-woocommerce');?></a>
        <?php endif; ?>
        <?php endif;?>
        <?php endif; ?>
    </h2>

    <form method="post" name="cleanup_options" action="options.php">

        <input type="hidden" name="mailchimp_woocommerce_settings_hidden" value="Y">

        <?php
        if (!$clicked_sync_button) {
            settings_fields($this->plugin_name);
            do_settings_sections($this->plugin_name);
            include('tabs/notices.php');
        }
        ?>

        <input type="hidden" name="<?php echo $this->plugin_name; ?>[mailchimp_active_tab]" value="<?php echo esc_attr($active_tab); ?>"/>

        <?php if ($active_tab == 'api_key' ): ?>
            <?php include_once 'tabs/api_key.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab == 'store_info' && $has_valid_api_key): ?>
            <?php include_once 'tabs/store_info.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab == 'campaign_defaults' ): ?>
            <?php include_once 'tabs/campaign_defaults.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab == 'newsletter_settings' ): ?>
            <?php include_once 'tabs/newsletter_settings.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab == 'sync' && $show_sync_tab): ?>
            <?php include_once 'tabs/store_sync.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab == 'logs' && $show_sync_tab): ?>
            <?php include_once 'tabs/logs.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab !== 'sync' && $active_tab !== 'logs') submit_button(__('Save all changes'), 'primary','submit', TRUE); ?>

    </form>

    <?php if ($active_tab == 'sync'): ?>
        <h2 style="padding-top: 1em;"><?php esc_html_e('More Information', 'mailchimp-woocommerce'); ?></h2>
        <ul>
            <li><?= sprintf(/* translators: %s - WP-CLI URL. */wp_kses( __( 'Have a larger store or having issues syncing? Consider using <a href=%s target=_blank>WP-CLI</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://github.com/mailchimp/mc-woocommerce/issues/158' ) );?></li>
            <li><?= esc_html__('Order and customer information will not sync if they contain an Amazon or generic email address.', 'mailchimp-woocommerce');?></li>
            <li><?= sprintf(/* translators: %s - Mailchimp Support URL. */wp_kses( __( 'Need help to connect your store? Visit the Mailchimp <a href=%s target=_blank>Knowledge Base</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-woocommerce/' ) );?></li>
            <li><?= sprintf(/* translators: %s - Plugin review URL. */wp_kses( __( 'Want to tell us how we\'re doing? <a href=%s target=_blank>Leave a review on Wordpress.org</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' ) );?></li>
            <li><?= sprintf(/* translators: %s - Mailchimp Privacy Policy URL. */wp_kses( __( 'By using this plugin, Mailchimp will process customer information in accordance with their <a href=%s target=_blank>Privacy Policy</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://mailchimp.com/legal/privacy/' ) );?></li>
        </ul>
    <?php endif; ?>

</div><!-- /.wrap -->
