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
        <p><?php echo $newsletter_settings_error; ?></p>
    </div>
<?php endif; ?>

<input type="hidden" name="mailchimp_active_settings_tab" value="newsletter_settings"/>

<h2 style="padding-top: 1em;"><?php esc_html_e('Audience Settings', 'mc-woocommerce');?></h2>
<p><?php esc_html_e('Please apply your audience settings. If you don\'t have an audience, you can choose to create one.', 'mc-woocommerce');?></p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Audience Name', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-list-label">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_list]" style="width:30%" required <?php if($list_is_configured): ?> disabled <?php endif; ?>>

            <?php if(!isset($allow_new_list) || $allow_new_list === true): ?>
            <option value="create_new"><?php esc_html_e('Create New Audience', 'mc-woocommerce');?></option>
            <?php endif ?>

            <?php if(isset($allow_new_list) && $allow_new_list === false): ?>
                <option value="">-- <?php esc_html_e('Select Audience', 'mc-woocommerce');?> --</option>
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
        <span><?php esc_html_e('Choose an audience to sync with your store.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Auto Subscribe On Initial Sync', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-auto-subscribe">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_auto_subscribe]" style="width:30%" required <?php if($list_is_configured): ?> disabled <?php endif; ?>>

            <?php
            $enable_auto_subscribe = (array_key_exists('mailchimp_auto_subscribe', $options) && !is_null($options['mailchimp_auto_subscribe'])) ? $options['mailchimp_auto_subscribe'] : '1';

            foreach (array('0' => esc_html__('No', 'mc-woocommerce'), '1' => esc_html__('Yes', 'mc-woocommerce')) as $key => $value ) {
                echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
            }
            ?>

        </select>
        <span><?php esc_html_e('During initial sync, auto subscribe the existing customers.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<h2 style="padding-top: 1em;"><?php esc_html_e('Opt-in Settings', 'mc-woocommerce');?></h2>
<p><?php esc_html_e('Add text to go along with the opt-in checkbox, and choose a default display option. Customers can click a box at checkout to opt in to your newsletter. Write a signup message and choose how you want this checkbox to appear.', 'mc-woocommerce');?> </p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Newsletter Label', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
        <textarea style="width: 30%;" rows="3" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label" name="<?php echo $this->plugin_name; ?>[newsletter_label]"><?php echo isset($options['newsletter_label']) ? esc_html($options['newsletter_label']) : esc_html__('Subscribe to our newsletter', 'mc-woocommerce'); ?></textarea>
        <span><?php esc_html_e('Enter text for the opt-in checkbox', $this->plugin_name); ?></span>
    </label>
    <p class="description"><?= esc_html(__('HTML tags allowed: <a href="" target="" title=""></a> and <br>', 'mc-woocommerce')); ?></p>
</fieldset>

<h4 style="padding-top: 1em;"><?php esc_html_e('Checkbox Display Options', 'mc-woocommerce');?></h4>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Checkbox Display Options', 'mc-woocommerce');?></span>
    </legend>
    <?php $checkbox_default_settings = (array_key_exists('mailchimp_checkbox_defaults', $options) && !is_null($options['mailchimp_checkbox_defaults'])) ? $options['mailchimp_checkbox_defaults'] : 'check'; ?>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="check"<?php if($checkbox_default_settings === 'check') echo ' checked="checked" '; ?>><?php esc_html_e('Visible, checked by default', 'mc-woocommerce');?><br>
    </label>
    <br/>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="uncheck"<?php if($checkbox_default_settings === 'uncheck') echo ' checked="checked" '; ?>><?php esc_html_e('Visible, unchecked by default', 'mc-woocommerce');?><br/>
    </label>
    <br/>
    <label>
        <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="hide"<?php if($checkbox_default_settings === 'hide') echo ' checked="checked" '; ?>><?php esc_html_e('Hidden, unchecked by default', 'mc-woocommerce');?><br/>
    </label>
</fieldset>

<h4 style="padding-top: 1em;"><?php esc_html_e('Advanced Checkbox Settings', 'mc-woocommerce');?></h4>
<p><?= sprintf(/* translators: %s - Woocommerce Actions documentation URL. */wp_kses( __( 'To change the location of the opt-in checkbox at checkout, input one of the <a href=%s target=_blank>available WooCommerce form actions.</a>.', 'mailchimp-woocommerce' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://docs.woocommerce.com/wc-apidocs/hook-docs.html' ) ); ?></p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Newsletter Checkbox Action', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_action]" value="<?php echo isset($options['mailchimp_checkbox_action']) ? $options['mailchimp_checkbox_action'] : 'woocommerce_after_checkout_billing_form' ?>" />
        <span><?php esc_html_e('Enter a WooCommerce form action', $this->plugin_name); ?></span>
    </label>
</fieldset>

<h2 style="padding-top: 1em;"><?php esc_html_e('Audience Member Tags', 'mc-woocommerce');?></h2>
<p><?= __( 'Add a comma separated list of tags to add to the user at Mailchimp', 'mailchimp-woocommerce' ); ?></p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Audience Member Tags', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-user-tags">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-user-tags" name="<?php echo $this->plugin_name; ?>[mailchimp_user_tags]" value="<?php echo isset($options['mailchimp_user_tags']) ? str_replace(',',', ',$options['mailchimp_user_tags']) : '' ?>" />
    </label>
</fieldset>

<h2 style="padding-top: 1em;"><?php esc_html_e('Product Image Size', 'mc-woocommerce');?></h2>
<p><?php esc_html_e('Define the product image size used by abandoned carts, order notifications, and product recommendations.', 'mc-woocommerce');?></p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Product Image Size', 'mc-woocommerce');?></span>
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
        <span><?php esc_html_e('Select an image size', $this->plugin_name); ?></span>
    </label>
</fieldset>
