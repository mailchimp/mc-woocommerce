<?php
/**
 * Review sync settings content template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

if ( ! isset( $handler ) ) {
	$handler = MailChimp_WooCommerce_Admin::instance();
}
// if we don't have a valid api key we need to redirect back to the 'api_key' tab.
if ( ! $handler->validateApiKey() || ( ! isset( $mailchimp_lists ) ) ) {
	$mailchimp_lists = $handler->getMailChimpLists();
	if ( false === $mailchimp_lists ) {
		wp_safe_redirect( 'admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key' );
		exit;
	}
}

$list_is_configured = isset( $options['mailchimp_list'] ) && ( ! empty( $options['mailchimp_list'] ) ) && array_key_exists( $options['mailchimp_list'], ( isset( $mailchimp_lists ) ? $mailchimp_lists : array() ) );

if ( ! isset( $options ) ) {
	$options = array();
}
$newsletter_settings_error = $this->getData( 'errors.mailchimp_list', false );

$checkout_page_id = get_option('woocommerce_checkout_page_id');
$mailchimp_customer_count = mailchimp_get_customer_lookup_count();
//$initial_sync_subscribe = ( array_key_exists( 'mailchimp_auto_subscribe', $options ) && ! is_null( $options['mailchimp_auto_subscribe'] ) ) ? (string) $options['mailchimp_auto_subscribe'] : '1';
//$ongoing_sync_subscribe = ( array_key_exists( 'mailchimp_ongoing_sync_status', $options ) && ! is_null( $options['mailchimp_ongoing_sync_status'] ) ) ? (string) $options['mailchimp_ongoing_sync_status'] : '1';
$initial_sync_subscribe = '1';
$ongoing_sync_subscribe = '1';
?>
<?php if ( $newsletter_settings_error ) : ?>
	<div class="error notice is-dismissable">
		<p><?php echo wp_kses_post( $newsletter_settings_error ); ?></p>
	</div>
<?php endif; ?>
<input type="hidden" name="mailchimp_active_settings_tab" value="newsletter_settings"/>
<div class="mc-wc-review-sync-settings-content">
    <div class="mc-wc-linked-audience">
        <div class="mc-wc-linked-audience-wrapper">
            <div class="mc-wc-linked-audience-list">
                <h3 class="mc-wc-settings-content-title"><?php esc_html_e( 'Linked audience', 'mailchimp-for-woocommerce' ); ?></h3>
                <div class="mc-wc-select-wrapper">
                    <select id="mailchimp_list_selector" class="mc-wc-select mc-wc-select-not-bold" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_list]" required <?php echo ( isset( $only_one_list ) && $only_one_list ) ? 'disabled' : ''; ?>>
                        <?php if ( ! isset( $allow_new_list ) || true === $allow_new_list ) : ?>
                            <option value="create_new"><?php esc_html_e( 'Create New Audience', 'mailchimp-for-woocommerce' ); ?></option>
                        <?php endif ?>
                        <?php if ( isset( $allow_new_list ) && false === $allow_new_list ) : ?>
                            <option value="">-- <?php esc_html_e( 'Select Audience', 'mailchimp-for-woocommerce' ); ?> --</option>
                        <?php endif; ?>
                        <?php
                        if ( isset( $mailchimp_lists ) && is_array( $mailchimp_lists ) ) {
                            $selected_list = isset( $options['mailchimp_list'] ) ? $options['mailchimp_list'] : null;
                            foreach ( $mailchimp_lists as $key => $value ) {
                                if (empty($selected_list)) $selected_list = $key;
                                echo '<option value="' . esc_attr( $key ) . '" ' . selected( ( (string) $key === (string) $selected_list), true, false ) . '>' . esc_html( $value ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <p class="mc-wc-linked-audience-description">
                <?php esc_html_e( 'Confirm the Mailchimp audience you want to associate with your WooCommerce store', 'mailchimp-for-woocommerce' ); ?>
                <span class="mc-wc-text-md store_name"><?php echo !empty($store_name) ? esc_attr($store_name) : esc_attr(get_option("siteurl")); ?>.</span>
            </p>
        </div>
    </div>
    <div class="mc-wc-import-customers-initial">
        <h3 class="mc-wc-settings-content-title"><?php esc_html_e( 'Import customers (initial sync)', 'mailchimp-for-woocommerce' ); ?></h3>
        <p class="mc-wc-text-1 pb-text">
            <?php esc_html_e( 'Choose how you’ll add your '.$mailchimp_customer_count.' WooCommerce customers to Mailchimp:', 'mailchimp-for-woocommerce' ); ?>
        </p>
        <div class="mc-wc-import-list-sync  <?php echo $initial_sync_subscribe; ?>">
            <div class="mc-wc-import-list-sync-item">
                <div class="mc-wc-import-list-sync-input">
                    <div class="mc-wc-radio">
                        <label class="mc-wc-radio-label">
                            <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_auto_subscribe]" value="1" <?php if ('1' === $initial_sync_subscribe){ echo "checked";} ?>>
                            <?php esc_html_e( 'Sync as subscribed', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-import-list-sync-description">
                    <?php esc_html_e( 'This status indicates that you\'ve gotten permission to market to your customers. Learn more about the ', 'mailchimp-for-woocommerce' ); ?> <a href="https://mailchimp.com/help/the-importance-of-permission/" target="_blank"><?php esc_html_e( 'importance of permission.', 'mailchimp-for-woocommerce' ); ?></a>
                </div>
            </div>
            <div class="mc-wc-import-list-sync-item">
                <div class="mc-wc-import-list-sync-input">
                    <div class="mc-wc-radio">
                        <label class="mc-wc-radio-label">
                            <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_auto_subscribe]" value="0" <?php echo '0' === $initial_sync_subscribe ? ' checked="checked" ' : ''; ?>>
                            <?php esc_html_e( 'Sync as non-subscribed', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-import-list-sync-description">
                    <?php esc_html_e( 'This status indicates you haven’t gotten permission to market to these customers. However, you can use Mailchimp to send ', 'mailchimp-for-woocommerce' ); ?><a href="https://mailchimp.com/help/about-non-subscribed-contacts/" target="_blank"><?php esc_html_e( 'non-subscribed contacts', 'mailchimp-for-woocommerce' ); ?></a> <?php esc_html_e( 'transactional emails and postcards and target them with ads.', 'mailchimp-for-woocommerce' ); ?>
                </div>
            </div>
            <div class="mc-wc-import-list-sync-item">
                <div class="mc-wc-import-list-sync-input">
                    <div class="mc-wc-radio">
                        <label class="mc-wc-radio-label">
                            <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_auto_subscribe]" value="2" <?php if ('2' === $initial_sync_subscribe){ echo "checked";} ?>>
                            <?php esc_html_e( 'Sync existing contacts only', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-import-list-sync-description">
                    <?php esc_html_e( 'Only WooCommerce customers who are already in your Mailchimp audience will sync. You won’t be able to send your other customers postcards or target them with ads.', 'mailchimp-for-woocommerce' ); ?>
                </div>
            </div>
        </div>
        <p class="mc-wc-text-2">
            <?php esc_html_e( 'If you choose to sync customers as subscribed or non-subscribed, you will need', 'mailchimp-for-woocommerce' ); ?> <a href="https://mailchimp.com/help/about-mailchimp-pricing-plans/" target="_blank"><?php esc_html_e( 'a Mailchimp plan', 'mailchimp-for-woocommerce' ); ?></a> <?php esc_html_e( 'that includes '.$mailchimp_customer_count.' contacts. If your plan does not include enough contacts, you will incur additional monthly charges.', 'mailchimp-for-woocommerce' ); ?> <a href="https://mailchimp.com/help/about-additional-charges/" target="_blank"><?php esc_html_e( 'Learn about additional charges.', 'mailchimp-for-woocommerce' ); ?></a>
        </p>
    </div>
    <div class="mc-wc-import-customers-ongoing">
        <h3 class="mc-wc-settings-content-title"><?php esc_html_e( 'Import customers (ongoing sync)', 'mailchimp-for-woocommerce' ); ?></h3>
        <div class="mc-wc-import-list-sync">
            <div class="mc-wc-import-list-sync-item">
                <div class="mc-wc-import-list-sync-input">
                    <div class="mc-wc-checkbox">
                        <label class="mc-wc-checkbox-label">
                            <input type="checkbox" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_ongoing_sync_status]" value="1" <?php if ((bool) $ongoing_sync_subscribe){ echo "checked"; } ?>>
                            <?php esc_html_e( 'Sync new non-subscribed contacts', 'mailchimp-for-woocommerce' ); ?>
                        </label>
                    </div>
                </div>
                <div class="mc-wc-import-list-sync-description">
                    <?php esc_html_e( 'Import new customers who haven’t opted in to receive your email marketing.  This setting must be active to use', 'mailchimp-for-woocommerce' ); ?> <a href="https://mailchimp.com/help/create-abandoned-cart-customer-journey/" target="_blank"><?php esc_html_e( 'Abandoned Cart automations.', 'mailchimp-for-woocommerce' ); ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="mc-wc-tag-customer">
        <div class="mc-wc-tag-customer-wrapper">
        <?php 
            $user_tags = ( array_key_exists( 'mailchimp_user_tags', $options ) && ! is_null( $options['mailchimp_user_tags'] ) ) ? esc_html( str_replace( ',', ', ', $options['mailchimp_user_tags'] )) : ''; 

            $user_tags_arr = array();

            if($user_tags) {
                $user_tags_arr = explode( ', ', $user_tags);
            }
            
        ?>
            <h3 class="mc-wc-settings-content-title"><?php esc_html_e( 'Tag WooCommerce customers', 'mailchimp-for-woocommerce' ); ?></h3>
            <p class="mc-wc-text-1">
                <?php esc_html_e( 'Tagging helps you filter your contacts and personalize your marketing in Mailchimp. Any tags created here will be applied to all contacts imported through this plugin.', 'mailchimp-for-woocommerce' ); ?>
            </p>
            <div class="mc-wc-tag-list">
                <div class="mc-wc-tag-form-input">
                    <input type="hidden" id="<?php echo esc_attr( $this->plugin_name ); ?>-user-tags" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_user_tags]" value="<?php echo $user_tags; ?>" />
                    <input class="mc-wc-input" type="text" placeholder="<?php esc_html_e( 'Enter tag', 'mailchimp-for-woocommerce' ); ?>">
                    <a class="mc-wc-btn-2 mc-wc-btn-2-outline btn-add">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15 10.5C15 11.0523 14.5523 11.5 14 11.5H11V14.5C11 15.0523 10.5523 15.5 10 15.5C9.44772 15.5 9 15.0523 9 14.5V11.5H6C5.44772 11.5 5 11.0523 5 10.5C5 9.94772 5.44772 9.5 6 9.5H9V6.5C9 5.94772 9.44772 5.5 10 5.5C10.5523 5.5 11 5.94772 11 6.5V9.5H14C14.5523 9.5 15 9.94772 15 10.5ZM10 2.5C5.582 2.5 2 6.082 2 10.5C2 14.918 5.582 18.5 10 18.5C14.418 18.5 18 14.918 18 10.5C18 6.082 14.418 2.5 10 2.5Z" fill="#7D57A4"/>
                        </svg>
                        <?php esc_html_e( 'Add', 'mailchimp-for-woocommerce' ); ?>
                    </a>
                </div>
                <div class="mc-wc-tag-show-tagged">
                <?php foreach ($user_tags_arr as $tag): ?>
                    <div>
                        <span class="mc-wc-tag-text"><?php echo $tag; ?></span>
                        <span class="mc-wc-tag-icon-del" data-value="<?php echo $tag; ?>"></span>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>