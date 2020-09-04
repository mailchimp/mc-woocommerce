<?php

$handler = MailChimp_WooCommerce_Admin::connect();

// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (!$handler->hasValidApiKey()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}
if (!$handler->hasValidStoreInfo()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=store_info&error_notice=missing_store');
}
?>

<input type="hidden" name="mailchimp_active_settings_tab" value="campaign_defaults"/>

<fieldset class="">
    <legend>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Audience Defaults', 'mailchimp-for-woocommerce');?></span>
    </legend>
    </legend>
    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-from-name-label"> 
            <?php esc_html_e('From Name', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="text" id="<?php echo $this->plugin_name; ?>-campaign-from-name-label" name="<?php echo $this->plugin_name; ?>[campaign_from_name]" value="<?php echo isset($options['campaign_from_name']) ? $options['campaign_from_name'] : get_option('blogname') ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-from-email-label">
            <?php esc_html_e('From Email', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="email" id="<?php echo $this->plugin_name; ?>-campaign-from-email-label" name="<?php echo $this->plugin_name; ?>[campaign_from_email]" value="<?php echo isset($options['campaign_from_email']) ? $options['campaign_from_email'] : get_option('admin_email') ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-subject-label">
            <?php esc_html_e('Subject', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="text" id="<?php echo $this->plugin_name; ?>-campaign-subject-label" name="<?php echo $this->plugin_name; ?>[campaign_subject]" value="<?php echo isset($options['campaign_subject']) ? $options['campaign_subject'] : esc_html__('Store Newsletter', 'mailchimp-for-woocommerce'); ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-language-label">
            <?php esc_html_e('Language', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        
        <div class="mailchimp-select-wrapper">
            <select id="<?php echo $this->plugin_name; ?>-campaign-language-label" name="<?php echo $this->plugin_name; ?>[campaign_language]" required>
                <?php $selected_locale = isset($options['store_locale']) && !empty($options['store_locale']) ? $options['store_locale'] : get_locale(); ?> ?>
                <?php
                foreach(MailChimp_Api_Locales::all() as $locale_key => $local_value) {
                    echo '<option value="' . esc_attr( $locale_key ) . '" ' . selected($locale_key === $selected_locale, true, false ) . '>' . esc_html( $local_value ) . '</option>';
                }
                ?>
            </select>    
        </div>
    </div>

    <div class="box">
        <label for="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label">
            <?php esc_html_e('Permission reminder message', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <textarea
            rows="4"
            id="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label"
            name="<?php echo $this->plugin_name; ?>[campaign_permission_reminder]"
            required><?php echo isset($options['campaign_permission_reminder']) ? $options['campaign_permission_reminder'] : sprintf(/* translators: %s - plugin name. */esc_html__( 'You were subscribed to the newsletter from %s', 'mailchimp-for-woocommerce' ),get_option('blogname'));?>
        </textarea>
    </div>

</fieldset>