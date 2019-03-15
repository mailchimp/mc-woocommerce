<?php

// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (!$handler->hasValidApiKey()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}

// if we don't have valid store information, we need to redirect back to the 'store_info' tab.
if (!$handler->hasValidStoreInfo()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=store_info&error_notice=missing_store');
}

// if we don't have a valid api key we need to redirect back to the 'api_key' tab.
if (!isset($mailchimp_lists) && ($mailchimp_lists = $handler->getMailChimpLists()) === false) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}

// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (empty($mailchimp_lists) && !$handler->hasValidCampaignDefaults()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=campaign_defaults&error_notice=missing_campaign_defaults');
}

$list_is_configured = isset($options['mailchimp_list']) && (!empty($options['mailchimp_list'])) && array_key_exists($options['mailchimp_list'], $mailchimp_lists);

?>

<?php if(($newsletter_settings_error = $this->getData('errors.mailchimp_list', false))) : ?>
    <div class="error notice is-dismissable">
        <p><?php _e($newsletter_settings_error, 'mailchimp-woocommerce'); ?></p>
    </div>
<?php endif; ?>

<input type="hidden" name="mailchimp_active_settings_tab" value="newsletter_settings"/>

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
                $selected_list = isset($options['mailchimp_list']) ? $options['mailchimp_list'] : null;
                foreach ($mailchimp_lists as $key => $value ) {
                    echo '<option value="' . esc_attr( $key ) . '" ' . selected(((string) $key === (string) $selected_list), true, false) . '>' . esc_html( $value ) . '</option>';
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

            foreach (array('0' => 'No', '1' => 'Yes') as $key => $value ) {
                echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
            }
            ?>

        </select>
        <span><?php esc_attr_e('During initial sync, auto subscribe the existing customers.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<h2 style="padding-top: 1em;">Opt-in Settings</h2>
<p>Add text to go along with the opt-in checkbox, and choose a default display option. Customers can click a box at checkout to opt in to your newsletter. Write a signup message and choose how you want this checkbox to appear. </p>

<fieldset>
    <legend class="screen-reader-text">
        <span>Newsletter Label</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label" name="<?php echo $this->plugin_name; ?>[newsletter_label]" value="<?php echo isset($options['newsletter_label']) ? $options['newsletter_label'] : 'Subscribe to our newsletter' ?>" />
        <span><?php esc_attr_e('Enter text for the opt-in checkbox', $this->plugin_name); ?></span>
    </label>
</fieldset>

<h4 style="padding-top: 1em;font-weight:normal;">Checkbox Display Options</h4>

<fieldset>
    <legend class="screen-reader-text">
        <span>Checkbox Display Options</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-defaults">
        <?php $checkbox_default_settings = (array_key_exists('mailchimp_checkbox_defaults', $options) && !is_null($options['mailchimp_checkbox_defaults'])) ? $options['mailchimp_checkbox_defaults'] : 'check'; ?>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="check"<?php if($checkbox_default_settings === 'check') echo ' checked="checked" '; ?>>Visible, checked by default<br>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="uncheck"<?php if($checkbox_default_settings === 'uncheck') echo ' checked="checked" '; ?>>Visible, unchecked by default<br/>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="hide"<?php if($checkbox_default_settings === 'hide') echo ' checked="checked" '; ?>>Hidden, unchecked by default<br/>
    </label>
</fieldset>

<h4 style="padding-top: 1em;font-weight:normal;">Advanced Checkbox Settings</h4>
<p>
    To change the location of the opt-in checkbox at checkout, input one of the
    <a href="https://docs.woocommerce.com/wc-apidocs/hook-docs.html" target="_blank">
        available WooCommerce form actions.
    </a>
</p>

<fieldset>
    <legend class="screen-reader-text">
        <span>Newsletter Checkbox Action</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_action]" value="<?php echo isset($options['mailchimp_checkbox_action']) ? $options['mailchimp_checkbox_action'] : 'woocommerce_after_checkout_billing_form' ?>" />
        <span><?php esc_attr_e('Enter a WooCommerce form action', $this->plugin_name); ?></span>
    </label>
</fieldset>

<h2 style="padding-top: 1em;">Product Image Size</h2>
<p>Define the product image size used by abandoned carts, order notifications, and product recommendations.</p>

<fieldset>
    <legend class="screen-reader-text">
        <span>Product Image Size</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-product_image_key">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_product_image_key]" style="width:30%">
            <?php
            $enable_auto_subscribe = (array_key_exists('mailchimp_product_image_key', $options) && !is_null($options['mailchimp_product_image_key'])) ? $options['mailchimp_product_image_key'] : 'medium';
            foreach (mailchimp_woocommerce_get_all_image_sizes_list() as $key => $value ) {
                echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
            }
            ?>
        </select>
        <span><?php esc_attr_e('Select an image size', $this->plugin_name); ?></span>
    </label>
</fieldset>
