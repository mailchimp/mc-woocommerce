<?php if(isset($_GET['error_notice'])): ?>
<div class="error notice is-dismissable">
    <?php
        switch($_GET['error_notice']) {
            case 'missing_api_key':
                esc_html_e('Mailchimp says: You must enter in a valid API key.', 'mailchimp-for-woocommerce');
            break;
            case 'missing_list':
                esc_html_e('Mailchimp says: You must select a marketing audience.', 'mailchimp-for-woocommerce');
                break;
            case 'missing_store':
                esc_html_e('Mailchimp says: Sorry you must set up your store before you proceed!', 'mailchimp-for-woocommerce');
                break;
            case 'not_ready_for_sync':
                esc_html_e('Mailchimp says: You are not fully ready to run the Store Sync, please verify your settings before proceeding.', 'mailchimp-for-woocommerce');
                break;
            default:

        }
    ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success_notice'])): ?>
    <div class="success notice is-dismissable">
        <?php
        switch($_GET['error_notice']) {
            case 're-sync-started':
                esc_html_e('Mailchimp says: Your re-sync has been started!', 'mailchimp-for-woocommerce');
                break;
            default:
        }
        ?>
    </div>
<?php endif; ?>

