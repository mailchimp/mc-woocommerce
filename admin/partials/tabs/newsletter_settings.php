<?php
// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (!$handler->hasValidApiKey()) {
    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}

// if we don't have valid store information, we need to redirect back to the 'store_info' tab.
if (!$handler->hasValidStoreInfo()) {
    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=store_info&error_notice=missing_store');
}

// if we don't have a valid api key we need to redirect back to the 'api_key' tab.
if (!isset($mailchimp_lists) && ($mailchimp_lists = $handler->getMailChimpLists()) === false) {
    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}

// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (empty($mailchimp_lists) && !$handler->hasValidCampaignDefaults()) {
    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=campaign_defaults&error_notice=missing_campaign_defaults');
}

$list_is_configured = isset($options['mailchimp_list']) && (!empty($options['mailchimp_list'])) && array_key_exists($options['mailchimp_list'], $mailchimp_lists);
?>

<?php if(($newsletter_settings_error = $this->getData('errors.mailchimp_list', false))) : ?>
    <div class="error notice is-dismissable">
        <p><?php _e($newsletter_settings_error, 'mailchimp-woocommerce'); ?></p>
    </div>
<?php endif; ?>

<h2 style="padding-top: 1em;">List Settings</h2>
<p>Please apply your list settings. If you don't have a list, you can choose to create one.</p>

<fieldset>
    <legend class="screen-reader-text">
        <span>List Name</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-list-label">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_list]" style="width:30%" required <?php if($list_is_configured): ?> disabled <?php endif; ?>>

            <?php if(!isset($allow_new_list) || $allow_new_list === true): ?>
            <option value="create_new">Create New List</option>
            <?php endif ?>

            <?php if(isset($allow_new_list) && $allow_new_list === false): ?>
                <option value="">-- Select List --</option>
            <?php endif; ?>

            <?php
            if (is_array($mailchimp_lists)) {
                foreach ($mailchimp_lists as $key => $value ) {
                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $options['mailchimp_list'], true, false ) . '>' . esc_html( $value ) . '</option>';
                }
            }
            ?>
        </select>
        <span><?php esc_attr_e('Choose a list to sync with your store.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span>Auto Subscribe On Initial Sync</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-auto-subscribe">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_auto_subscribe]" style="width:30%" required <?php if($list_is_configured): ?> disabled <?php endif; ?>>

            <?php
            $enable_auto_subscribe = (array_key_exists('mailchimp_auto_subscribe', $options) && !is_null($options['mailchimp_auto_subscribe'])) ? $options['mailchimp_auto_subscribe'] : '1';

            foreach (['0' => 'No', '1' => 'Yes'] as $key => $value ) {
                echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
            }
            ?>

        </select>
        <span><?php esc_attr_e('During initial sync, auto subscribe the existing customers.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span>MailChimp Newsletter Label</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label" name="<?php echo $this->plugin_name; ?>[newsletter_label]" value="<?php echo isset($options['newsletter_label']) ? $options['newsletter_label'] : 'Subscribe to our newsletter' ?>" />
        <span><?php esc_attr_e('Write a subscribe message for customers at checkout.', $this->plugin_name); ?></span>
    </label>
</fieldset>
