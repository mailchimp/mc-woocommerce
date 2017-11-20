<?php if(isset($_GET['error_notice'])): ?>
<div class="error notice is-dismissable">
    <?php
        switch($_GET['error_notice']) {
            case 'missing_api_key':
                _e('MailChimp says: You must enter in a valid API key.', 'mailchimp-woocommerce');
            break;
            case 'missing_campaign_defaults':
                _e('MailChimp says: Sorry you must set up your campaign defaults before you proceed!', 'mailchimp-woocommerce');
                break;
            case 'missing_list':
                _e('MailChimp says: You must select a marketing list.', 'mailchimp-woocommerce');
                break;
            case 'missing_store':
                _e('MailChimp says: Sorry you must set up your store before you proceed!', 'mailchimp-woocommerce');
                break;
            case 'not_ready_for_sync':
                _e('MailChimp says: You are not fully ready to run the Store Sync, please verify your settings before proceeding.', 'mailchimp-woocommerce');
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
                _e('MailChimp says: Your re-sync has been started!', 'mailchimp-woocommerce');
                break;
            default:
        }
        ?>
    </div>
<?php endif; ?>

