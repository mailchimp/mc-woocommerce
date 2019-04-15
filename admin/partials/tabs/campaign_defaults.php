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

<h2 style="padding-top: 1em;"><?php _e('Audience Defaults', $this->plugin_name);?></h2>
<p><?php esc_html_e('Please fill out the default campaign information.', $this->plugin_name);?></p>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Contact Name', $this->plugin_name)?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-campaign-from-name-label">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-campaign-from-name-label" name="<?php echo $this->plugin_name; ?>[campaign_from_name]" value="<?php echo isset($options['campaign_from_name']) ? $options['campaign_from_name'] : '' ?>" required/>
        <?php
            esc_html_e('Default from name', $this->plugin_name);
            if (empty($options['campaign_from_name']) ) {
                echo '<span style="color:red;">*</span>';
            }
        ?>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('From Email', $this->plugin_name)?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-campaign-from-email-label">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-campaign-from-email-label" name="<?php echo $this->plugin_name; ?>[campaign_from_email]" value="<?php echo isset($options['campaign_from_email']) ? $options['campaign_from_email'] : get_option('admin_email') ?>" required/>
        <?php
            esc_html_e('Default from email', $this->plugin_name);
            if (empty($options['campaign_from_email']) ) {
                echo '<span style="color:red;">*</span>';
            }
        ?>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Default subject', $this->plugin_name)?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-campaign-subject-label">
        <input style="width: 30%;" type="text" id="<?php echo $this->plugin_name; ?>-campaign-subject-label" name="<?php echo $this->plugin_name; ?>[campaign_subject]" value="<?php echo isset($options['campaign_subject']) ? $options['campaign_subject'] : get_option('blogname') ?>" required/>
        <?php
            esc_html_e('Default subject', $this->plugin_name);
            if (empty($options['campaign_subject']) ) {
                echo '<span style="color:red;">*</span>';
            }
        ?>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Default language', $this->plugin_name)?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-campaign-language-label">
        <select id="<?php echo $this->plugin_name; ?>-campaign-language-label" name="<?php echo $this->plugin_name; ?>[campaign_language]" style="width:30%" required>
            <?php $selected_locale = isset($options['campaign_language']) && !empty($options['campaign_language']) ? $options['campaign_language'] : 'en'; ?>
            <?php
            foreach(MailChimp_Api_Locales::simple() as $locale_key => $local_value) {
                echo '<option value="' . esc_attr( $locale_key ) . '" ' . selected($locale_key === $selected_locale, true, false ) . '>' . esc_html( $local_value ) . '</option>';
            }
            ?>
        </select>
        <?php
            esc_html_e('Default language', $this->plugin_name);
            if (empty($options['campaign_language']) ) {
                echo '<span style="color:red;">*</span>';
            }
        ?>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Permission Reminder', $this->plugin_name)?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label">
        <textarea 
            style="width: 30%;"
            id="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label"
            name="<?php echo $this->plugin_name; ?>[campaign_permission_reminder]"
            required><?php echo isset($options['campaign_permission_reminder']) ? $options['campaign_permission_reminder'] : sprintf(/* translators: %s - plugin name. */esc_html__( 'You were subscribed to the newsletter from %s', $this->plugin_name ),get_option('blogname'));?></textarea>
        <?php
            esc_html_e('Permission reminder message', $this->plugin_name);
            if (empty($options['campaign_permission_reminder']) ) {
                echo '<span style="color:red;">*</span>';
            }
        ?>
    </label>
</fieldset>
